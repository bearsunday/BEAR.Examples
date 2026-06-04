SELECT
    at.article_id AS articleId,
    t.id,
    t.slug,
    t.name
FROM tags t
JOIN article_tags at ON at.tag_id = t.id
WHERE at.article_id IN (:articleIds)
ORDER BY at.article_id, t.name
