#!/usr/bin/env python3
"""
Observe fake data and generate JSON Schemas (semantic-ex Phase 2 + 3).
maxLength = observed max * ~1.5 rounded up; minLength = observed min.
"""
import json
import math
from pathlib import Path

FAKE = Path("/Users/akihito/git/BEAR.Cms/var/fake")
OUT  = Path("/Users/akihito/git/BEAR.Cms/var/json_schema")
OUT.mkdir(parents=True, exist_ok=True)

BASE_ID = "https://example.com/bear-cms"

def ceil_to_nice(n):
    # round max up to a nice boundary (like 50, 100, 200, 500, 1000, ...)
    if n <= 50: return 50
    if n <= 100: return 100
    if n <= 200: return 200
    if n <= 500: return 500
    if n <= 1000: return 1000
    if n <= 2000: return 2000
    if n <= 5000: return 5000
    if n <= 10000: return 10000
    return int(math.ceil(n * 1.5 / 1000)) * 1000

def observe_str(values):
    nonnull = [v for v in values if v is not None]
    nulls = len(values) - len(nonnull)
    lengths = [len(str(v)) for v in nonnull]
    return {
        "nulls": nulls,
        "min_len": min(lengths) if lengths else 0,
        "max_len": max(lengths) if lengths else 0,
        "typical_len": sorted(lengths)[len(lengths)//2] if lengths else 0,
    }

def observe_int(values):
    nonnull = [v for v in values if v is not None]
    nulls = len(values) - len(nonnull)
    return {
        "nulls": nulls,
        "min": min(nonnull) if nonnull else 0,
        "max": max(nonnull) if nonnull else 0,
    }

def field_list(records, key):
    return [r.get(key) for r in records]

def str_field_schema(records, key, *, description, format_=None, pattern=None, nullable=False):
    obs = observe_str(field_list(records, key))
    if obs["max_len"] == 0 and obs["min_len"] == 0 and obs["nulls"] > 0:
        min_len = 0
        max_len = 100
    else:
        min_len = obs["min_len"]
        max_len = ceil_to_nice(obs["max_len"])
    schema = {
        "type": ["string", "null"] if nullable else "string",
        "description": description,
        "minLength": min_len,
        "maxLength": max_len,
    }
    if format_:
        schema["format"] = format_
    if pattern:
        schema["pattern"] = pattern
    return schema

def int_field_schema(records, key, *, description, nullable=False, minimum=None):
    obs = observe_int(field_list(records, key))
    schema = {
        "type": ["integer", "null"] if nullable else "integer",
        "description": description,
    }
    if minimum is not None:
        schema["minimum"] = minimum
    else:
        schema["minimum"] = obs["min"]
    return schema

# -- load fake data -------------------------------------------------------
articles   = json.loads((FAKE / "article.json").read_text())
categories = json.loads((FAKE / "category.json").read_text())
tags       = json.loads((FAKE / "tag.json").read_text())
authors    = json.loads((FAKE / "author.json").read_text())
media      = json.loads((FAKE / "media.json").read_text())

link_def = {
    "type": "object",
    "required": ["href"],
    "properties": {
        "href": {"type": "string", "format": "uri-reference"}
    }
}

# -- Article schema -------------------------------------------------------
article_schema = {
    "$schema": "https://json-schema.org/draft/2020-12/schema",
    "$id": f"{BASE_ID}/article",
    "title": "Article",
    "description": "ALPS Article state. Single article with embedded Author, Category and Tags.",
    "type": "object",
    "required": ["id", "slug", "title", "body", "status", "authorId", "categoryId"],
    "properties": {
        "id": int_field_schema(articles, "id", description="Primary key", minimum=1),
        "slug": str_field_schema(articles, "slug", description="URL-safe unique slug", pattern="^[a-z0-9][a-z0-9-]*$"),
        "title": str_field_schema(articles, "title", description="Article headline"),
        "body": str_field_schema(articles, "body", description="Full article body"),
        "excerpt": str_field_schema(articles, "excerpt", description="Short summary used in list views"),
        "status": {
            "type": "string",
            "description": "Lifecycle status",
            "enum": ["draft", "published"]
        },
        "publishedAt": {
            "type": ["string", "null"],
            "description": "Publication timestamp; null for drafts",
            "format": "date-time"
        },
        "authorId": int_field_schema(articles, "authorId", description="Foreign key to Author", minimum=1),
        "categoryId": int_field_schema(articles, "categoryId", description="Foreign key to Category", minimum=1),
        "_links": {
            "type": "object",
            "description": "HAL links. goArticleList always present; doUpdateArticle/doDeleteArticle for write operations.",
            "properties": {
                "self": {"$ref": "#/$defs/link"},
                "goArticleList": {"$ref": "#/$defs/link"},
                "goAuthor": {"$ref": "#/$defs/link"},
                "goCategory": {"$ref": "#/$defs/link"},
                "doUpdateArticle": {"$ref": "#/$defs/link"},
                "doDeleteArticle": {"$ref": "#/$defs/link"}
            }
        },
        "_embedded": {
            "type": "object",
            "description": "Embedded Author, Category and Tag resources",
            "properties": {
                "author": {"$ref": f"{BASE_ID}/author"},
                "category": {"$ref": f"{BASE_ID}/category"},
                "tags": {
                    "type": "array",
                    "items": {"$ref": f"{BASE_ID}/tag"}
                }
            }
        }
    },
    "$defs": {"link": link_def}
}

# -- ArticleList schema ---------------------------------------------------
article_list_schema = {
    "$schema": "https://json-schema.org/draft/2020-12/schema",
    "$id": f"{BASE_ID}/articleList",
    "title": "ArticleList",
    "description": "Paginated list of articles",
    "type": "object",
    "required": ["items", "page", "perPage", "totalCount"],
    "properties": {
        "items": {
            "type": "array",
            "items": {"$ref": f"{BASE_ID}/article"}
        },
        "page": {"type": "integer", "minimum": 1, "description": "Current page (1-indexed)"},
        "perPage": {"type": "integer", "minimum": 1, "maximum": 100, "description": "Items per page"},
        "totalCount": {"type": "integer", "minimum": 0, "description": "Total matching articles"},
        "_links": {
            "type": "object",
            "properties": {
                "self": {"$ref": "#/$defs/link"},
                "next": {"$ref": "#/$defs/link"},
                "prev": {"$ref": "#/$defs/link"},
                "first": {"$ref": "#/$defs/link"},
                "last": {"$ref": "#/$defs/link"},
                "goArticle": {"$ref": "#/$defs/link"},
                "doCreateArticle": {"$ref": "#/$defs/link"}
            }
        }
    },
    "$defs": {"link": link_def}
}

# -- Category schema ------------------------------------------------------
category_schema = {
    "$schema": "https://json-schema.org/draft/2020-12/schema",
    "$id": f"{BASE_ID}/category",
    "title": "Category",
    "description": "ALPS Category state",
    "type": "object",
    "required": ["id", "slug", "name"],
    "properties": {
        "id": int_field_schema(categories, "id", description="Primary key", minimum=1),
        "slug": str_field_schema(categories, "slug", description="URL-safe unique slug", pattern="^[a-z0-9][a-z0-9-]*$"),
        "name": str_field_schema(categories, "name", description="Display name"),
        "description": str_field_schema(categories, "description", description="Optional description", nullable=True),
        "parentId": int_field_schema(categories, "parentId", description="Parent category id, null for top-level", nullable=True, minimum=1),
        "_links": {
            "type": "object",
            "properties": {
                "self": {"$ref": "#/$defs/link"},
                "goCategoryList": {"$ref": "#/$defs/link"},
                "goArticleList": {"$ref": "#/$defs/link"},
                "doUpdateCategory": {"$ref": "#/$defs/link"},
                "doDeleteCategory": {"$ref": "#/$defs/link"}
            }
        }
    },
    "$defs": {"link": link_def}
}

# -- CategoryList schema --------------------------------------------------
category_list_schema = {
    "$schema": "https://json-schema.org/draft/2020-12/schema",
    "$id": f"{BASE_ID}/categoryList",
    "title": "CategoryList",
    "description": "List of categories",
    "type": "object",
    "required": ["items"],
    "properties": {
        "items": {"type": "array", "items": {"$ref": f"{BASE_ID}/category"}},
        "totalCount": {"type": "integer", "minimum": 0},
        "_links": {
            "type": "object",
            "properties": {
                "self": {"$ref": "#/$defs/link"},
                "goCategory": {"$ref": "#/$defs/link"},
                "doCreateCategory": {"$ref": "#/$defs/link"}
            }
        }
    },
    "$defs": {"link": link_def}
}

# -- Tag schema -----------------------------------------------------------
tag_schema = {
    "$schema": "https://json-schema.org/draft/2020-12/schema",
    "$id": f"{BASE_ID}/tag",
    "title": "Tag",
    "description": "ALPS Tag state",
    "type": "object",
    "required": ["id", "slug", "name"],
    "properties": {
        "id": int_field_schema(tags, "id", description="Primary key", minimum=1),
        "slug": str_field_schema(tags, "slug", description="URL-safe unique slug", pattern="^[a-z0-9][a-z0-9-]*$"),
        "name": str_field_schema(tags, "name", description="Display name"),
        "_links": {
            "type": "object",
            "properties": {
                "self": {"$ref": "#/$defs/link"},
                "goTagList": {"$ref": "#/$defs/link"},
                "goArticleList": {"$ref": "#/$defs/link"},
                "doDeleteTag": {"$ref": "#/$defs/link"}
            }
        }
    },
    "$defs": {"link": link_def}
}

# -- TagList schema -------------------------------------------------------
tag_list_schema = {
    "$schema": "https://json-schema.org/draft/2020-12/schema",
    "$id": f"{BASE_ID}/tagList",
    "title": "TagList",
    "description": "List of tags",
    "type": "object",
    "required": ["items"],
    "properties": {
        "items": {"type": "array", "items": {"$ref": f"{BASE_ID}/tag"}},
        "totalCount": {"type": "integer", "minimum": 0},
        "_links": {
            "type": "object",
            "properties": {
                "self": {"$ref": "#/$defs/link"},
                "goTag": {"$ref": "#/$defs/link"},
                "doCreateTag": {"$ref": "#/$defs/link"}
            }
        }
    },
    "$defs": {"link": link_def}
}

# -- Author schema --------------------------------------------------------
author_schema = {
    "$schema": "https://json-schema.org/draft/2020-12/schema",
    "$id": f"{BASE_ID}/author",
    "title": "Author",
    "description": "ALPS Author state",
    "type": "object",
    "required": ["id", "name", "email"],
    "properties": {
        "id": int_field_schema(authors, "id", description="Primary key", minimum=1),
        "name": str_field_schema(authors, "name", description="Full name"),
        "email": str_field_schema(authors, "email", description="Email address", format_="email"),
        "bio": str_field_schema(authors, "bio", description="Optional biography; empty allowed"),
        "_links": {
            "type": "object",
            "properties": {
                "self": {"$ref": "#/$defs/link"},
                "goArticleList": {"$ref": "#/$defs/link"},
                "doUpdateAuthor": {"$ref": "#/$defs/link"}
            }
        }
    },
    "$defs": {"link": link_def}
}

# -- Media schema ---------------------------------------------------------
media_schema = {
    "$schema": "https://json-schema.org/draft/2020-12/schema",
    "$id": f"{BASE_ID}/media",
    "title": "Media",
    "description": "ALPS Media state",
    "type": "object",
    "required": ["id", "filename", "mimeType", "url"],
    "properties": {
        "id": int_field_schema(media, "id", description="Primary key", minimum=1),
        "filename": str_field_schema(media, "filename", description="Original filename"),
        "mimeType": str_field_schema(media, "mimeType", description="MIME type"),
        "url": str_field_schema(media, "url", description="Public URL path", format_="uri-reference"),
        "alt": str_field_schema(media, "alt", description="Accessibility alt text", nullable=True),
        "width": int_field_schema(media, "width", description="Pixel width; 0 for SVG", minimum=0),
        "height": int_field_schema(media, "height", description="Pixel height; 0 for SVG", minimum=0),
        "_links": {
            "type": "object",
            "properties": {
                "self": {"$ref": "#/$defs/link"},
                "doDeleteMedia": {"$ref": "#/$defs/link"}
            }
        }
    },
    "$defs": {"link": link_def}
}

# -- Write observations notes ---------------------------------------------
notes = []
def observe_and_log(label, records, fields):
    lines = [f"\n## {label}"]
    for f in fields:
        vals = field_list(records, f)
        if all(isinstance(v, (int, type(None))) for v in vals):
            obs = observe_int(vals)
            lines.append(f"- `{f}` (int): min={obs['min']} max={obs['max']} nulls={obs['nulls']}/{len(vals)}")
        else:
            obs = observe_str(vals)
            lines.append(f"- `{f}` (str): min_len={obs['min_len']} max_len={obs['max_len']} typical={obs['typical_len']} nulls={obs['nulls']}/{len(vals)}")
    notes.extend(lines)

notes.append("# Fake data observations (semantic-ex Phase 2)")
notes.append(f"Generated from {FAKE}//*.json — 50 records per atomic entity.")
observe_and_log("Article", articles, ["id","slug","title","body","excerpt","status","publishedAt","authorId","categoryId"])
observe_and_log("Category", categories, ["id","slug","name","description","parentId"])
observe_and_log("Tag", tags, ["id","slug","name"])
observe_and_log("Author", authors, ["id","name","email","bio"])
observe_and_log("Media", media, ["id","filename","mimeType","url","alt","width","height"])

(FAKE / "observations.md").write_text("\n".join(notes) + "\n")

# -- Write schemas --------------------------------------------------------
def write(name, schema):
    (OUT / f"{name}.json").write_text(json.dumps(schema, indent=2, ensure_ascii=False) + "\n")

write("article", article_schema)
write("articleList", article_list_schema)
write("category", category_schema)
write("categoryList", category_list_schema)
write("tag", tag_schema)
write("tagList", tag_list_schema)
write("author", author_schema)
write("media", media_schema)

print("Wrote schemas to", OUT)
for p in sorted(OUT.iterdir()):
    print(" ", p.name)
