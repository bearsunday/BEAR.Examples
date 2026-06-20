<?php
/**
 * @var list<\BEAR\Examples\Entity\Article> $articles
 */
?>
{{ setLayout('layout/Default') }}
{{ setBlock('bodyClass') ~}}public public-index{{ endBlock() }}
{{ setBlock('header') ~}}<h1 class="ArticleList sr-only">Articles</h1>{{ endBlock() }}
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
