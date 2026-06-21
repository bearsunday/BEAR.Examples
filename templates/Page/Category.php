<?php
/**
 * @var \BEAR\Kata\Entity\Category|null $category
 * @var list<\BEAR\Kata\Entity\Article> $articles
 */
if (! isset($category) || $category === null) {
    throw new \BEAR\Kata\Exception\CategoryNotFoundException();
}
?>
{{ setLayout('layout/Default') }}
{{ setBlock('bodyClass') ~}}public public-detail public-category{{ endBlock() }}
{{ setBlock('title') ~}}{{h $category->name }} - BEAR.Kata{{ endBlock() }}
{{ setBlock('header') ~}}<h1 class="Category">Category Detail</h1>{{ endBlock() }}
<main>
  <section class="Category">
    <input type="hidden" class="id" value="{{h $category->id }}">
    <h2 class="name">{{h $category->name }}</h2>
    <span class="slug">{{h $category->slug }}</span>
    <?php if ($category->description !== null): ?>
      <p class="description">{{h $category->description }}</p>
    <?php endif ?>
    <?php if ($category->parentId !== null): ?>
      <p>Parent category: <a class="goCategory" href="/category?id={{h $category->parentId }}">#{{h $category->parentId }}</a></p>
    <?php endif ?>
  </section>

  <section class="ArticleList">
    <h2>Articles in this category</h2>
    <?php if ($articles === []): ?>
      <p>No articles in this category yet.</p>
    <?php endif ?>
    <?php foreach ($articles as $article): ?>
      <article class="Article">
        <input type="hidden" class="id" value="{{h $article->id }}">
        <h3 class="title">
          <a class="goArticle" href="/article?id={{h $article->id }}">{{h $article->title }}</a>
        </h3>
        <span class="slug">{{h $article->slug }}</span>
        <?php $publishedAtLabel = $article->publishedAtLabel(); ?>
        <?php if ($publishedAtLabel !== null): ?>
          <time class="publishedAt" datetime="{{h $publishedAtLabel }}">{{h $publishedAtLabel }}</time>
        <?php endif ?>
      </article>
    <?php endforeach ?>
  </section>
</main>
<nav class="Related">
  <ul>
    <li><a href="/articlelist?categoryId={{h $category->id }}" class="goArticleList">All articles in {{h $category->name }}</a></li>
  </ul>
</nav>
