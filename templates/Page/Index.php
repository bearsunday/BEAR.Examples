<?php
/**
 * @var list<\MyVendor\Cms\Entity\Article> $articles
 */
?>
{{ setLayout('layout/Default') }}
{{ setBlock('header') ~}}<h1 class="ArticleList">BEAR.Cms Reference CMS</h1>{{ endBlock() }}
<main>
  <section class="ArticleList">
    <?php if ($articles === []): ?>
      <p>No published articles.</p>
    <?php endif ?>
    <?php foreach ($articles as $article): ?>
      <article class="Article">
        <input type="hidden" class="id" value="{{h $article->id }}">
        <h2 class="title">
          <a class="goArticle" href="/article?id={{h $article->id }}">{{h $article->title }}</a>
        </h2>
        <span class="slug">{{h $article->slug }}</span>
        <span class="status">{{h $article->status->value }}</span>
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
<nav>
  <h2>Browse</h2>
  <ul>
    <li><a href="/articlelist" class="goArticleList">All articles</a></li>
    <li><a href="/authorlist" class="goAuthorList">Authors</a></li>
    <li><a href="/categorylist" class="goCategoryList">Categories</a></li>
    <li><a href="/taglist" class="goTagList">Tags</a></li>
    <li><a href="/admin/articlelist" class="goAdminArticleList">Article administration</a></li>
    <li><a href="/mock/html/article.html">Static design mock (no PHP)</a></li>
  </ul>
</nav>
