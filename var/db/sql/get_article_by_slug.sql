SELECT
    id,
    slug,
    title,
    body,
    excerpt,
    status,
    published_at,
    author_id,
    category_id
FROM articles
WHERE slug = :slug
LIMIT 1
