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
WHERE id = :id
LIMIT 1
