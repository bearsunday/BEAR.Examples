<?php
/**
 * @var list<\BEAR\Examples\Entity\Article> $articles
 * @var array{status:string|null} $filter
 * @var int $page
 * @var int $perPage
 * @var bool $hasNext
 * @var bool $deleted
 */
$qs = static function (array $params): string {
    $clean = array_filter($params, static fn ($v) => $v !== null && $v !== '');

    return $clean === [] ? '' : '?' . http_build_query($clean);
};
?>
{{ setLayout('layout/Default') }}
{{ setBlock('bodyClass') ~}}admin admin-list admin-articlelist{{ endBlock() }}
{{ setBlock('title') ~}}Article Administration - BEAR.Examples{{ endBlock() }}
{{ setBlock('header') ~}}<h1 class="AdminArticleList">Article Administration</h1>{{ endBlock() }}
<main>
  <?php if ($deleted): ?>
    <p class="notice">Article deleted.</p>
  <?php endif ?>

  <nav class="AdminActions">
    <a class="doCreateArticle" href="/admin/article">New article</a>
  </nav>

  <form class="AdminFilter" method="get" action="/admin/articlelist">
    <label for="status">Status</label>
    <select id="status" name="status">
      <option value="" <?php if ($filter['status'] === null): ?>selected<?php endif ?>>All</option>
      <option value="draft" <?php if ($filter['status'] === 'draft'): ?>selected<?php endif ?>>Draft</option>
      <option value="published" <?php if ($filter['status'] === 'published'): ?>selected<?php endif ?>>Published</option>
    </select>
    <input type="hidden" name="perPage" value="{{a $perPage }}">
    <button type="submit">Filter</button>
  </form>

  <section class="ArticleList">
    <span class="page">{{h $page }}</span>
    <span class="perPage">{{h $perPage }}</span>
    <?php if ($articles === []): ?>
      <p>No articles match the current filter.</p>
    <?php endif ?>
    <?php foreach ($articles as $article): ?>
      <?php
      $viewUrl = '/article?id=' . $article->id;
      $editUrl = '/admin/article?id=' . $article->id;
      $deleteUrl = '/admin/articledelete?id=' . $article->id;
      ?>
      <article class="Article">
        <input type="hidden" class="id" value="{{a $article->id }}">
        <h2 class="title">
          <a class="goArticle" {{a ['href' => $viewUrl] }}>{{h $article->title }}</a>
        </h2>
        <span class="slug">{{h $article->slug }}</span>
        <span class="status" data-status="{{h $article->status->value }}">{{h $article->status->value }}</span>
        <nav class="AdminActions">
          <a class="doUpdateArticle" {{a ['href' => $editUrl] }}>Edit</a>
          <a class="doDeleteArticle" {{a ['href' => $deleteUrl] }}>Delete</a>
        </nav>
      </article>
    <?php endforeach ?>
  </section>
</main>
<nav>
  <h2>Navigation</h2>
  <ul>
    <li><a href="/admin/index" class="goAdminIndex">Admin home</a></li>
    <?php if ($page > 1): ?>
      <?php $prevUrl = '/admin/articlelist' . $qs(['status' => $filter['status'], 'page' => $page - 1, 'perPage' => $perPage]); ?>
      <li><a class="goPrev" {{a ['href' => $prevUrl] }}>Previous page</a></li>
    <?php endif ?>
    <?php if ($hasNext): ?>
      <?php $nextUrl = '/admin/articlelist' . $qs(['status' => $filter['status'], 'page' => $page + 1, 'perPage' => $perPage]); ?>
      <li><a class="goNext" {{a ['href' => $nextUrl] }}>Next page</a></li>
    <?php endif ?>
    <li><a href="/articlelist" class="goArticleList">Public article list</a></li>
    <li><a href="/" class="goIndex">Home</a></li>
  </ul>
</nav>
