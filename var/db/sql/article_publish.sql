UPDATE articles
SET status = :status,
    published_at = :publishedAt
WHERE id = :id
