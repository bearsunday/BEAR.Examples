UPDATE articles
SET title = :title,
    body = :body,
    excerpt = :excerpt,
    status = :status,
    published_at = :publishedAt
WHERE id = :id
