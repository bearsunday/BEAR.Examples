<?php
/**
 * @var list<\BEAR\Examples\Entity\Article> $articles
 * @var array{categoryId:int|null, tagId:int|null, authorId:int|null, status:string|null} $filter
 * @var \BEAR\Examples\Entity\Category|null $category
 * @var \BEAR\Examples\Entity\Tag|null $tag
 * @var \BEAR\Examples\Entity\Author|null $author
 * @var int $page
 * @var int $perPage
 * @var bool $hasNext
 */
$qs = static function (array $params): string {
    $clean = array_filter($params, static fn ($v) => $v !== null && $v !== '');
    return $clean === [] ? '' : '?' . http_build_query($clean);
};
?>
{{ setLayout('layout/Default') }}
{{ setBlock('bodyClass') ~}}public public-list public-articlelist{{ endBlock() }}
{{ setBlock('title') ~}}Article List - BEAR.Examples{{ endBlock() }}
{{ setBlock('header') ~}}
<h1 class="ArticleList">Article List</h1>
<?php if ($category !== null): ?>
  <p>Filtered by category: <span class="name">{{h $category->name }}</span></p>
<?php endif ?>
<?php if ($tag !== null): ?>
  <p>Filtered by tag: <span class="name">{{h $tag->name }}</span></p>
<?php endif ?>
<?php if ($author !== null): ?>
  <p>Filtered by author: <span class="name">{{h $author->name }}</span></p>
<?php endif ?>
<?php if ($filter['status'] !== null): ?>
  <p>Filtered by status: <span class="status">{{h $filter['status'] }}</span></p>
<?php endif ?>
{{ endBlock() }}
<main>
  <section class="ArticleList">
    <span class="page">{{h $page }}</span>
    <span class="perPage">{{h $perPage }}</span>
    <?php if ($articles === []): ?>
      <p>No articles match the current filter.</p>
    <?php endif ?>
    <?php foreach ($articles as $article): ?>
      <article class="Article">
        <input type="hidden" class="id" value="{{h $article->id }}">
        <h2 class="title">
          <a class="goArticle" href="/article?id={{h $article->id }}">{{h $article->title }}</a>
        </h2>
        <span class="slug">{{h $article->slug }}</span>
        <span class="status" data-status="{{h $article->status->value }}">{{h $article->status->value }}</span>
        <?php $publishedAtLabel = $article->publishedAtLabel(); ?>
        <?php if ($publishedAtLabel !== null): ?>
          <time class="publishedAt" datetime="{{h $publishedAtLabel }}">{{h $publishedAtLabel }}</time>
        <?php endif ?>
        <?php $summary = $article->summary(); ?>
        <?php if ($summary !== null): ?>
          <p class="excerpt">{{h $summary }}</p>
        <?php endif ?>
      </article>
    <?php endforeach ?>
  </section>
</main>
<nav class="Pagination">
  <ul>
    <?php if ($page > 1): ?>
      <?php $prevUrl = '/articlelist' . $qs(['categoryId' => $filter['categoryId'], 'tagId' => $filter['tagId'], 'authorId' => $filter['authorId'], 'status' => $filter['status'], 'page' => $page - 1, 'perPage' => $perPage]); ?>
      <li><a class="goPrev" {{a ['href' => $prevUrl] }}>Previous page</a></li>
    <?php endif ?>
    <?php if ($hasNext): ?>
      <?php $nextUrl = '/articlelist' . $qs(['categoryId' => $filter['categoryId'], 'tagId' => $filter['tagId'], 'authorId' => $filter['authorId'], 'status' => $filter['status'], 'page' => $page + 1, 'perPage' => $perPage]); ?>
      <li><a class="goNext" {{a ['href' => $nextUrl] }}>Next page</a></li>
    <?php endif ?>
  </ul>
</nav>
