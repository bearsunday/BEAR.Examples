SELECT
    id,
    slug,
    title,
    body,
    excerpt,
    status,
    published_at AS publishedAt,
    author_id AS authorId,
    category_id AS categoryId
FROM articles
WHERE id = :id
LIMIT 1
