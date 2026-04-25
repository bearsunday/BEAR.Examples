SELECT
    t.id,
    t.slug,
    t.name
FROM tags t
JOIN article_tags at ON at.tag_id = t.id
WHERE at.article_id = :articleId
ORDER BY t.name
