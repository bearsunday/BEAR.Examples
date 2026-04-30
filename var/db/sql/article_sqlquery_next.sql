SELECT
    id,
    slug,
    title,
    published_at AS publishedAt
FROM articles
WHERE status = 'published'
  AND published_at IS NOT NULL
  AND (
    published_at > :publishedAt
    OR (published_at = :publishedAt AND id > :id)
  )
ORDER BY published_at ASC, id ASC
LIMIT 1
