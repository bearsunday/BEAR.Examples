# Term Usage Index

This index reports lexical identifier matches only; it does not prove semantic equivalence.

## Summary

- Terms used in API: 40
- Terms with same-name ALPS descriptor: 26
- Lexical ALPS coverage: 65%
- Reserved representation fields: 0
- ☑︎ = ALPS descriptor binding

## Terms

### `alt` ☑︎

- title: Alt Text
- doc: Accessibility text describing the media.
- usages:
  - parameter: POST /media {alt}
  - parameter: POST /media-upload {alt}
  - schema property: media_create.json#/properties/alt
  - schema property: media_upload.json#/properties/alt
  - schema property: media.json#/properties/alt

### `articleId` ☑︎

- title: Article ID (filter)
- usages:
  - parameter: GET /cache/article-tags {articleId}
  - parameter: PUT /cache/article-tags {articleId}
  - parameter: GET /crawl/tags {articleId}
  - parameter: GET /tags {articleId}
  - schema property: cache_article_tags_update.json#/properties/articleId
  - schema property: cache_article_tags.json#/properties/articleId
  - schema property: crawl_tag_list.json#/items/properties/articleId

### `articleList`

- usages:
  - schema property: crawl_author.json#/properties/articleList

### `authorId` ☑︎

- title: Author ID (FK)
- usages:
  - parameter: POST /article {authorId}
  - parameter: GET /articles {authorId}
  - parameter: GET /cache/author-profile {authorId}
  - parameter: GET /crawl/articles {authorId}
  - schema property: article_create.json#/properties/authorId
  - schema property: article.json#/properties/authorId
  - schema property: articleList.json#/properties/items/items/properties/authorId
  - schema property: author_profile.json#/properties/authorId
  - schema property: cache_article_preview.json#/properties/authorId
  - schema property: crawl_article_list.json#/items/properties/authorId

### `authorizationUrl`

- usages:
  - schema property: auth_authorization.json#/properties/authorizationUrl

### `bio` ☑︎

- title: Biography
- def: https://schema.org/description
- usages:
  - parameter: POST /author {bio}
  - parameter: PUT /author {bio}
  - parameter: PUT /cache/author {bio}
  - schema property: author_create.json#/properties/bio
  - schema property: author_update.json#/properties/bio
  - schema property: author.json#/properties/bio
  - schema property: crawl_author.json#/properties/bio

### `body` ☑︎

- title: Article Body
- def: https://schema.org/articleBody
- usages:
  - parameter: POST /article {body}
  - parameter: PUT /article {body}
  - schema property: article_create.json#/properties/body
  - schema property: article_update.json#/properties/body
  - schema property: article.json#/properties/body

### `cachePattern`

- usages:
  - schema property: author_profile.json#/properties/cachePattern
  - schema property: cache_article_preview.json#/properties/cachePattern
  - schema property: cache_article_tags.json#/properties/cachePattern

### `categoryId` ☑︎

- title: Category ID (FK)
- usages:
  - parameter: POST /article {categoryId}
  - parameter: GET /articles {categoryId}
  - schema property: article_create.json#/properties/categoryId
  - schema property: article.json#/properties/categoryId
  - schema property: articleList.json#/properties/items/items/properties/categoryId
  - schema property: crawl_article_list.json#/items/properties/categoryId

### `code`

- usages:
  - parameter: POST /auth {code}
  - schema property: auth_exchange.json#/properties/code

### `count`

- usages:
  - schema property: articleList.json#/properties/count
  - schema property: cache_article_tags.json#/properties/count

### `dependencyUri`

- usages:
  - schema property: author_profile.json#/properties/dependencyUri

### `description` ☑︎

- title: Description
- def: https://schema.org/description
- usages:
  - parameter: POST /category {description}
  - parameter: PUT /category {description}
  - schema property: category_create.json#/properties/description
  - schema property: category_update.json#/properties/description
  - schema property: category.json#/properties/description

### `email` ☑︎

- title: Email
- def: https://schema.org/email
- usages:
  - parameter: POST /author {email}
  - parameter: PUT /author {email}
  - parameter: PUT /cache/author {email}
  - schema property: author_create.json#/properties/email
  - schema property: author_update.json#/properties/email
  - schema property: auth_response.json#/properties/email
  - schema property: author.json#/properties/email
  - schema property: crawl_author.json#/properties/email
  - schema property: write_response.json#/properties/email

### `excerpt` ☑︎

- title: Excerpt
- doc: Short summary used in list views.
- usages:
  - parameter: POST /article {excerpt}
  - parameter: PUT /article {excerpt}
  - schema property: article_create.json#/properties/excerpt
  - schema property: article_update.json#/properties/excerpt
  - schema property: article.json#/properties/excerpt
  - schema property: articleList.json#/properties/items/items/properties/excerpt
  - schema property: crawl_article_list.json#/items/properties/excerpt

### `file` ☑︎

- title: Uploaded File
- doc: Binary file body resolved by Ray.InputQuery #[InputFile].
- usages:
  - parameter: POST /media-upload {file}
  - schema property: media_upload.json#/properties/file

### `filename` ☑︎

- title: Filename
- usages:
  - parameter: POST /media {filename}
  - schema property: media_create.json#/properties/filename
  - schema property: media.json#/properties/filename
  - schema property: write_response.json#/properties/filename

### `height` ☑︎

- title: Height
- def: https://schema.org/height
- usages:
  - parameter: POST /media {height}
  - schema property: media_create.json#/properties/height
  - schema property: media.json#/properties/height

### `id` ☑︎

- title: Identifier
- def: https://schema.org/identifier
- usages:
  - parameter: GET /article {id}
  - parameter: PUT /article {id}
  - parameter: DELETE /article {id}
  - parameter: POST /article-publish {id}
  - parameter: GET /author {id}
  - parameter: PUT /author {id}
  - parameter: GET /cache/article-preview {id}
  - parameter: GET /cache/author {id}
  - parameter: PUT /cache/author {id}
  - parameter: GET /cache/tag {id}
  - parameter: PUT /cache/tag {id}
  - parameter: GET /category {id}
  - parameter: PUT /category {id}
  - parameter: DELETE /category {id}
  - parameter: GET /crawl/author {id}
  - parameter: GET /media {id}
  - parameter: DELETE /media {id}
  - parameter: GET /tag {id}
  - parameter: DELETE /tag {id}
  - parameter: GET /variations/article-as-array {id}
  - parameter: GET /variations/article-raw-pdo {id}
  - parameter: GET /variations/article-sql-query {id}
  - parameter: GET /variations/media-stream {id}
  - schema property: article_publish.json#/properties/id
  - schema property: article_update.json#/properties/id
  - schema property: author_update.json#/properties/id
  - schema property: category_update.json#/properties/id
  - schema property: tag_update.json#/properties/id
  - schema property: article.json#/properties/id
  - schema property: articleList.json#/properties/items/items/properties/id
  - schema property: auth_response.json#/properties/id
  - schema property: author.json#/properties/id
  - schema property: cache_article_preview.json#/properties/id
  - schema property: category.json#/properties/id
  - schema property: crawl_article_list.json#/items/properties/id
  - schema property: crawl_author.json#/properties/id
  - schema property: crawl_tag_list.json#/items/properties/id
  - schema property: media.json#/properties/id
  - schema property: tag.json#/properties/id
  - schema property: write_response.json#/properties/id

### `items`

- usages:
  - schema property: articleList.json#/properties/items
  - schema property: cache_article_tags.json#/properties/items
  - schema property: categoryList.json#/properties/items
  - schema property: tagList.json#/properties/items

### `message`

- usages:
  - schema property: auth_response.json#/properties/message
  - schema property: write_response.json#/properties/message

### `mimeType` ☑︎

- title: MIME Type
- def: https://schema.org/encodingFormat
- usages:
  - parameter: POST /media {mimeType}
  - schema property: media_create.json#/properties/mimeType
  - schema property: media.json#/properties/mimeType

### `name` ☑︎

- title: Name
- def: https://schema.org/name
- usages:
  - parameter: POST /author {name}
  - parameter: PUT /author {name}
  - parameter: PUT /cache/author {name}
  - parameter: PUT /cache/tag {name}
  - parameter: POST /category {name}
  - parameter: PUT /category {name}
  - parameter: POST /tag {name}
  - schema property: author_create.json#/properties/name
  - schema property: author_update.json#/properties/name
  - schema property: category_create.json#/properties/name
  - schema property: category_update.json#/properties/name
  - schema property: tag_create.json#/properties/name
  - schema property: tag_update.json#/properties/name
  - schema property: auth_response.json#/properties/name
  - schema property: author.json#/properties/name
  - schema property: category.json#/properties/name
  - schema property: crawl_author.json#/properties/name
  - schema property: crawl_tag_list.json#/items/properties/name
  - schema property: tag.json#/properties/name

### `page` ☑︎

- title: Page Number
- doc: 1-indexed pagination page.
- usages:
  - parameter: GET /articles {page}
  - schema property: articleList.json#/properties/page

### `parentId` ☑︎

- title: Parent Category ID (self-FK)
- doc: Nullable reference for hierarchical categories.
- usages:
  - parameter: POST /category {parentId}
  - parameter: PUT /category {parentId}
  - schema property: category_create.json#/properties/parentId
  - schema property: category_update.json#/properties/parentId
  - schema property: category.json#/properties/parentId

### `perPage` ☑︎

- title: Per-Page Count
- usages:
  - parameter: GET /articles {perPage}
  - parameter: GET /crawl/articles {perPage}
  - schema property: articleList.json#/properties/perPage

### `provider`

- usages:
  - schema property: auth_response.json#/properties/provider

### `publishedAt` ☑︎

- title: Published At
- def: https://schema.org/datePublished
- usages:
  - parameter: POST /article {publishedAt}
  - parameter: PUT /article {publishedAt}
  - parameter: POST /article-publish {publishedAt}
  - schema property: article_create.json#/properties/publishedAt
  - schema property: article_publish.json#/properties/publishedAt
  - schema property: article_update.json#/properties/publishedAt
  - schema property: article.json#/properties/publishedAt
  - schema property: articleList.json#/properties/items/items/properties/publishedAt
  - schema property: crawl_article_list.json#/items/properties/publishedAt
  - schema property: write_response.json#/properties/publishedAt

### `reason`

- usages:
  - schema property: auth_response.json#/properties/reason

### `slug` ☑︎

- title: URL-safe Slug
- doc: Lower-case kebab-case unique identifier used in human-readable URIs.
- usages:
  - parameter: POST /article {slug}
  - parameter: PUT /cache/tag {slug}
  - parameter: POST /category {slug}
  - parameter: POST /tag {slug}
  - schema property: article_create.json#/properties/slug
  - schema property: category_create.json#/properties/slug
  - schema property: tag_create.json#/properties/slug
  - schema property: tag_update.json#/properties/slug
  - schema property: article.json#/properties/slug
  - schema property: articleList.json#/properties/items/items/properties/slug
  - schema property: category.json#/properties/slug
  - schema property: crawl_article_list.json#/items/properties/slug
  - schema property: crawl_tag_list.json#/items/properties/slug
  - schema property: tag.json#/properties/slug
  - schema property: write_response.json#/properties/slug

### `state`

- usages:
  - parameter: POST /auth {state}
  - schema property: auth_exchange.json#/properties/state

### `status` ☑︎

- title: Lifecycle Status
- doc: draft or published.
- usages:
  - parameter: POST /article {status}
  - parameter: PUT /article {status}
  - parameter: GET /articles {status}
  - schema property: article_create.json#/properties/status
  - schema property: article_update.json#/properties/status
  - schema property: article.json#/properties/status
  - schema property: articleList.json#/properties/items/items/properties/status
  - schema property: cache_article_preview.json#/properties/status
  - schema property: crawl_article_list.json#/items/properties/status
  - schema property: write_response.json#/properties/status

### `subject`

- usages:
  - schema property: auth_response.json#/properties/subject

### `tagId` ☑︎

- title: Tag ID (filter)
- usages:
  - parameter: GET /articles {tagId}

### `tagIds`

- usages:
  - parameter: POST /article {tagIds}
  - parameter: PUT /article {tagIds}
  - parameter: PUT /cache/article-tags {tagIds}
  - schema property: article_create.json#/properties/tagIds
  - schema property: article_update.json#/properties/tagIds
  - schema property: cache_article_tags_update.json#/properties/tagIds

### `tagList`

- usages:
  - schema property: crawl_article_list.json#/items/properties/tagList

### `title` ☑︎

- title: Title
- def: https://schema.org/headline
- usages:
  - parameter: POST /article {title}
  - parameter: PUT /article {title}
  - schema property: article_create.json#/properties/title
  - schema property: article_update.json#/properties/title
  - schema property: article.json#/properties/title
  - schema property: articleList.json#/properties/items/items/properties/title
  - schema property: cache_article_preview.json#/properties/title
  - schema property: crawl_article_list.json#/items/properties/title

### `totalCount` ☑︎

- title: Total Count
- usages:
  - schema property: articleList.json#/properties/totalCount
  - schema property: categoryList.json#/properties/totalCount
  - schema property: tagList.json#/properties/totalCount

### `url` ☑︎

- title: URL
- def: https://schema.org/contentUrl
- usages:
  - parameter: POST /media {url}
  - schema property: media_create.json#/properties/url
  - schema property: media.json#/properties/url

### `width` ☑︎

- title: Width
- def: https://schema.org/width
- usages:
  - parameter: POST /media {width}
  - schema property: media_create.json#/properties/width
  - schema property: media.json#/properties/width
