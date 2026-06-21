<?php
/**
 * @var \BEAR\Kata\Entity\Tag|null $tag
 * @var list<\BEAR\Kata\Entity\Article> $articles
 */
if (! isset($tag) || $tag === null) {
    throw new \BEAR\Kata\Exception\TagNotFoundException();
}
?>
{{ setLayout('layout/Default') }}
{{ setBlock('bodyClass') ~}}public public-detail public-tag{{ endBlock() }}
{{ setBlock('title') ~}}{{h $tag->name }} - BEAR.Kata{{ endBlock() }}
{{ setBlock('header') ~}}<h1 class="Tag">Tag Detail</h1>{{ endBlock() }}
<main>
  <section class="Tag">
    <input type="hidden" class="id" value="{{h $tag->id }}">
    <h2 class="name">{{h $tag->name }}</h2>
    <span class="slug">{{h $tag->slug }}</span>
  </section>

  <section class="ArticleList">
    <h2>Tagged articles</h2>
    <?php if ($articles === []): ?>
      <p>No articles tagged with this tag yet.</p>
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
    <li><a href="/articlelist?tagId={{h $tag->id }}" class="goArticleList">All articles tagged {{h $tag->name }}</a></li>
  </ul>
</nav>
