#!/usr/bin/env python3
"""
Generate 50 realistic fake records per entity for BEAR.Cms.
Referential integrity:
  article.authorId   in authors
  article.categoryId in categories
  article_tags bridge table uses tag ids + article ids
"""
import json
import random
from pathlib import Path

random.seed(42)

OUT = Path("/Users/akihito/git/BEAR.Cms/var/fake")
OUT.mkdir(parents=True, exist_ok=True)

# -- Authors --------------------------------------------------------------
first_en = ["Emma", "Liam", "Olivia", "Noah", "Ava", "Mia", "Lucas", "Sophia",
            "Ethan", "Isabella", "Mason", "Amelia", "Leo", "Charlotte", "Jack",
            "Harper", "Oliver", "Evelyn", "Jacob", "Ella", "James", "Scarlett",
            "Henry", "Grace", "Daniel"]
last_en  = ["Smith", "Johnson", "Williams", "Brown", "Jones", "Garcia",
            "Miller", "Davis", "Rodriguez", "Martinez", "Hernandez", "Lopez",
            "Gonzalez", "Wilson", "Anderson", "Thomas", "Taylor", "Moore",
            "Jackson", "Martin", "Lee", "Perez", "Thompson", "White", "Harris"]
first_ja = ["田中", "佐藤", "鈴木", "高橋", "伊藤", "渡辺", "山本", "中村",
            "小林", "加藤", "吉田", "山田", "佐々木", "山口", "松本", "井上",
            "木村", "林", "清水", "斎藤"]
given_ja = ["太郎", "花子", "健一", "美咲", "直樹", "あかり", "翔太", "陽子",
            "大輔", "さくら", "修平", "由美", "雄太", "香織", "拓海"]
bios = [
    "Full-stack engineer blogging about PHP internals.",
    "Editor-in-chief. Loves long-form storytelling.",
    "Front-end developer, typography nerd.",
    "Travel writer covering Southeast Asia.",
    "Freelance photographer and essayist.",
    "テックブログ執筆者。BEAR.Sundayが好き。",
    "料理と発酵食品について書いています。",
    "Writer. Cat person. Occasional poet.",
    "SRE turned technical writer.",
    "Data journalist covering climate topics.",
    "新米ライター。コーヒーと本をこよなく愛する。",
    "DevRel at a small startup.",
    "",  # empty bio allowed
    "I write about sustainable gardening.",
    "UX designer; sometimes I write too.",
]

authors = []
used_emails = set()
for i in range(1, 51):
    if i % 3 == 0:
        name = random.choice(first_ja) + " " + random.choice(given_ja)
    else:
        name = random.choice(first_en) + " " + random.choice(last_en)
    # email must be ASCII-safe regardless of display name
    base = "".join(c for c in name.lower() if c.isascii() and (c.isalnum() or c == " ")).replace(" ", ".").strip(".")
    if not base:
        base = f"author{i}"
    email = f"{base}{i}@example.com"
    while email in used_emails:
        email = f"{base}{i}.{random.randint(10,99)}@example.com"
    used_emails.add(email)
    authors.append({
        "id": i,
        "name": name,
        "email": email,
        "bio": random.choice(bios),
    })

# -- Categories -----------------------------------------------------------
cat_defs = [
    ("technology", "Technology"),
    ("programming", "Programming"),
    ("php", "PHP"),
    ("javascript", "JavaScript"),
    ("architecture", "Architecture"),
    ("design", "Design"),
    ("travel", "Travel"),
    ("food", "Food"),
    ("photography", "Photography"),
    ("books", "Books"),
    ("lifestyle", "Lifestyle"),
    ("culture", "Culture"),
    ("health", "Health"),
    ("science", "Science"),
    ("business", "Business"),
    ("news", "News"),
    ("opinion", "Opinion"),
    ("interviews", "Interviews"),
    ("tutorials", "Tutorials"),
    ("case-studies", "Case Studies"),
    ("release-notes", "Release Notes"),
    ("deep-dives", "Deep Dives"),
    ("short-reads", "Short Reads"),
    ("beginner", "Beginner"),
    ("advanced", "Advanced"),
    ("web", "Web"),
    ("mobile", "Mobile"),
    ("devops", "DevOps"),
    ("cloud", "Cloud"),
    ("databases", "Databases"),
    ("testing", "Testing"),
    ("performance", "Performance"),
    ("security", "Security"),
    ("accessibility", "Accessibility"),
    ("ai", "AI"),
    ("machine-learning", "Machine Learning"),
    ("open-source", "Open Source"),
    ("community", "Community"),
    ("career", "Career"),
    ("events", "Events"),
    ("book-reviews", "Book Reviews"),
    ("commentary", "Commentary"),
    ("editorial", "Editorial"),
    ("guides", "Guides"),
    ("references", "References"),
    ("tools", "Tools"),
    ("frameworks", "Frameworks"),
    ("libraries", "Libraries"),
    ("standards", "Standards"),
    ("history", "History of Computing"),
]
cat_descriptions = [
    None,
    "Posts about coding practice, patterns and language internals.",
    "Essays, tips and reference material.",
    "Field reports and case studies.",
    "Short daily notes.",
    "Long-form technical deep dives.",
    "日本語での記事をまとめます。",
    "",
]
categories = []
for i, (slug, name) in enumerate(cat_defs, start=1):
    parent = None
    if i > 10 and i % 4 == 0:
        parent = random.randint(1, 10)  # top-level parents
    categories.append({
        "id": i,
        "slug": slug,
        "name": name,
        "description": random.choice(cat_descriptions),
        "parentId": parent,
    })

# -- Tags -----------------------------------------------------------------
tag_defs = [
    ("bear-sunday", "BEAR.Sunday"),
    ("ray-di", "Ray.Di"),
    ("ray-aop", "Ray.Aop"),
    ("media-query", "MediaQuery"),
    ("alps", "ALPS"),
    ("hal", "HAL"),
    ("rest", "REST"),
    ("tutorial", "Tutorial"),
    ("intro", "Introduction"),
    ("advanced", "Advanced"),
    ("php", "PHP"),
    ("php8", "PHP 8"),
    ("attributes", "Attributes"),
    ("di", "Dependency Injection"),
    ("cqrs", "CQRS"),
    ("bdr", "BDR Pattern"),
    ("testing", "Testing"),
    ("phpunit", "PHPUnit"),
    ("doctrine", "Doctrine"),
    ("migrations", "Migrations"),
    ("mysql", "MySQL"),
    ("sqlite", "SQLite"),
    ("javascript", "JavaScript"),
    ("typescript", "TypeScript"),
    ("vue", "Vue"),
    ("react", "React"),
    ("nginx", "Nginx"),
    ("docker", "Docker"),
    ("malt", "Malt"),
    ("opinion", "Opinion"),
    ("news", "News"),
    ("release", "Release"),
    ("performance", "Performance"),
    ("security", "Security"),
    ("best-practice", "Best Practice"),
    ("design-pattern", "Design Pattern"),
    ("beginner", "Beginner"),
    ("pro-tip", "Pro Tip"),
    ("photo-essay", "Photo Essay"),
    ("travel-log", "Travel Log"),
    ("review", "Review"),
    ("interview", "Interview"),
    ("case-study", "Case Study"),
    ("workflow", "Workflow"),
    ("tooling", "Tooling"),
    ("accessibility", "Accessibility"),
    ("community", "Community"),
    ("event", "Event"),
    ("career", "Career"),
    ("japanese", "日本語"),
]
tags = [{"id": i, "slug": slug, "name": name} for i, (slug, name) in enumerate(tag_defs, start=1)]

# -- Articles -------------------------------------------------------------
titles = [
    "Getting Started with BEAR.Sunday",
    "Why Ray.MediaQuery Changes How We Write SQL",
    "A Deep Dive into ALPS Profiles",
    "HAL+JSON in Practice",
    "BDR Pattern Explained",
    "Migrating from Phinx to Doctrine Migrations",
    "Testing BEAR Resources without a Database",
    "The Hidden Power of PHP Attributes",
    "Composing Modules with Ray.Di",
    "From CRUD to Semantic REST",
    "BEAR.Cms アーキテクチャ概観",
    "ALPSで設計を共有する",
    "Fakeデータから制約を発見する",
    "Ray.MediaQueryのBDR実装例",
    "小さなCMSの育て方",
    "Semantic-Ex Method: An Introduction",
    "Building a Reference CMS for BEAR.Sunday",
    "Content Modeling: Articles, Categories, Tags",
    "Why We Dropped JS from Our Admin",
    "HAL Links and Embedded Resources",
    "Domain-Driven Design on a Thin Slice",
    "Observing Data to Derive Constraints",
    "Pagination without Pain",
    "The Case Against Premature Abstraction",
    "Notes on Long-Form Tech Writing",
    "Working with Readonly Classes in PHP 8.2",
    "Handling Transactions in a Resource-Oriented App",
    "Two Weeks with Malt",
    "Setting Up Doctrine Migrations in BEAR",
    "Using Doctrine DBAL for Schema Diffs",
    "Type Hints over Comments",
    "When Your Fake is Better than Your Prod",
    "Weekend Notes: Sketching an API",
    "Cache Invalidation for Writers",
    "A Tour of BEAR.QueryRepository",
    "Refactoring Toward a Repository Boundary",
    "The Joy of a Tiny Codebase",
    "Writing Migrations You Won't Regret",
    "Nginx, PHP-FPM and Malt: A Short Guide",
    "Designing URIs with ALPS in Mind",
    "What HAL Taught Me about APIs",
    "Interviewing Without a Whiteboard",
    "Photo Essay: Kyoto in Spring",
    "A Traveler's Reading List",
    "Recipes from a Tiny Kitchen",
    "The Unreasonable Effectiveness of README.md",
    "The Plural of Anecdote is Data",
    "One Script, Many Screens",
    "A Day with a New Framework",
    "Notes Toward a Better Onboarding",
]
bodies_short = [
    "A brief note.",
    "Quick thoughts on the topic.",
    "TL;DR included at the top.",
]
bodies_long_en = [
    "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.",
    "In this article we walk through a concrete example: how we modeled a minimal CMS using BEAR.Sunday, and how the BDR pattern shaped the read side. We also discuss the write side and how it fits in.\n\nWe start by reviewing the ALPS profile, then we derive JSON Schema from observed data, and finally we generate stubs for the Query interfaces.",
    "When you treat your data as the source of truth about constraints (rather than choosing arbitrary limits up front), surprising things happen. Some fields that you assumed were 'short strings' turn out to commonly reach 200+ characters. Others you thought would be large are almost always under 50 characters.\n\nThis is the semantic-ex method applied to practice.",
]
bodies_long_ja = [
    "この記事ではBEAR.Sundayを用いた参照実装CMSを紹介します。BDRパターンにより読み込み側と書き込み側を分離し、保守しやすいコードベースを目指します。\n\n読み込み側はRay.MediaQueryでSQLを外出しし、書き込み側はRay.InputQueryで入力をオブジェクト化します。",
    "ALPSプロファイルをまず書き、Fakeデータを生成してから制約を発見するというアプローチは、現実のデータに裏打ちされたスキーマを生むという意味で大きな利点があります。",
    "短いメモ。",
]
articles = []
slugs_used = set()
for i in range(1, 51):
    title = titles[(i - 1) % len(titles)]
    if i > len(titles):
        title = title + f" (part {1 + (i-1) // len(titles)})"
    # slug
    base_slug = "".join(c if c.isalnum() else "-" for c in title.lower()).strip("-")
    while "--" in base_slug:
        base_slug = base_slug.replace("--", "-")
    if any(ord(c) > 127 for c in base_slug):
        # contains non-ascii; strip to ascii bits
        base_slug = "".join(c for c in base_slug if c.isascii()).strip("-") or f"article-{i}"
    slug = base_slug[:60]
    j = 2
    while slug in slugs_used:
        slug = f"{base_slug[:55]}-{j}"
        j += 1
    slugs_used.add(slug)
    # body
    if i % 7 == 0:
        body = random.choice(bodies_short)
    elif i % 3 == 0:
        body = random.choice(bodies_long_ja)
    else:
        body = random.choice(bodies_long_en)
    # excerpt - first 80 chars
    excerpt = body.split("\n")[0][:140]
    # status
    status = "draft" if i % 6 == 0 else "published"
    published_at = None if status == "draft" else f"2026-{((i-1) % 12) + 1:02d}-{((i-1) % 28) + 1:02d}T{9 + (i % 8):02d}:{(i * 7) % 60:02d}:00Z"
    articles.append({
        "id": i,
        "slug": slug,
        "title": title,
        "body": body,
        "excerpt": excerpt,
        "status": status,
        "publishedAt": published_at,
        "authorId": ((i - 1) % len(authors)) + 1,
        "categoryId": ((i - 1) % len(categories)) + 1,
    })

# -- ArticleTag -----------------------------------------------------------
article_tags = []
for a in articles:
    k = random.randint(1, 4)
    selected = random.sample(range(1, len(tags) + 1), k)
    for t in selected:
        article_tags.append({"articleId": a["id"], "tagId": t})

# -- Media ----------------------------------------------------------------
mime_types = ["image/jpeg", "image/png", "image/webp", "image/gif", "image/svg+xml"]
alt_samples = [
    "A minimalist workspace with a laptop and coffee cup.",
    "Mountain peaks in winter light.",
    "Screenshot of a BEAR.Sunday console output.",
    "Portrait of a developer smiling at their screen.",
    "Diagram illustrating the BDR pattern.",
    "",
    None,
    "桜の並木道",
    "キーボードの俯瞰写真",
    "ホワイトボード上のスケッチ",
    "A stack of technical books.",
    "Kitchen counter with a cutting board and knife.",
    "Architectural detail of a temple roof.",
    "City skyline at dusk.",
    "A cat curled up on a keyboard.",
]
media = []
for i in range(1, 51):
    mime = mime_types[(i - 1) % len(mime_types)]
    ext = "jpg" if mime == "image/jpeg" else mime.split("/")[1].split("+")[0]
    filename = f"media-{i:03d}.{ext}"
    url = f"/media/{filename}"
    if mime == "image/svg+xml":
        w, h = 0, 0  # svg not necessarily sized
    else:
        w = random.choice([320, 640, 800, 1024, 1280, 1600, 1920])
        h = random.choice([240, 480, 600, 768, 960, 1080, 1200])
    alt = alt_samples[(i - 1) % len(alt_samples)]
    media.append({
        "id": i,
        "filename": filename,
        "mimeType": mime,
        "url": url,
        "alt": alt,
        "width": w,
        "height": h,
    })

# -- Write files ----------------------------------------------------------
(OUT / "author.json").write_text(json.dumps(authors, indent=2, ensure_ascii=False))
(OUT / "category.json").write_text(json.dumps(categories, indent=2, ensure_ascii=False))
(OUT / "tag.json").write_text(json.dumps(tags, indent=2, ensure_ascii=False))
(OUT / "article.json").write_text(json.dumps(articles, indent=2, ensure_ascii=False))
(OUT / "articleTag.json").write_text(json.dumps(article_tags, indent=2, ensure_ascii=False))
(OUT / "media.json").write_text(json.dumps(media, indent=2, ensure_ascii=False))

# list/pagination wrappers
(OUT / "articleList.json").write_text(json.dumps({
    "items": [{k: v for k, v in a.items() if k != "body"} for a in articles[:20]],
    "page": 1, "perPage": 20, "totalCount": len(articles)
}, indent=2, ensure_ascii=False))
(OUT / "categoryList.json").write_text(json.dumps({
    "items": categories[:30],
    "page": 1, "perPage": 30, "totalCount": len(categories)
}, indent=2, ensure_ascii=False))
(OUT / "tagList.json").write_text(json.dumps({
    "items": tags,
    "page": 1, "perPage": 50, "totalCount": len(tags)
}, indent=2, ensure_ascii=False))

print(f"authors={len(authors)} categories={len(categories)} tags={len(tags)} articles={len(articles)} articleTags={len(article_tags)} media={len(media)}")
