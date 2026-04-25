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
WHERE (:categoryId IS NULL OR a.category_id = :categoryId)
  AND (:status IS NULL OR a.status = :status)
  AND (
    :tagId IS NULL
    OR EXISTS (SELECT 1 FROM article_tags at WHERE at.article_id = a.id AND at.tag_id = :tagId)
  )
ORDER BY a.published_at DESC, a.id DESC
LIMIT :limit OFFSET :offset
