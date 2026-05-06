SELECT
    a.id,
    a.slug,
    a.title,
    a.body,
    a.excerpt,
    a.status,
    a.published_at,
    a.author_id,
    a.category_id
FROM articles a
WHERE (:status IS NULL OR a.status = :status)
ORDER BY a.published_at DESC, a.id DESC
