<?php
/**
 * @var \MyVendor\Cms\Result\ArticleSelection $articles
 * @var \DateTimeImmutable $now
 */
?>
{{ setLayout('layout/Default') }}
{{ setBlock('bodyClass') ~}}public public-list public-articlefeed{{ endBlock() }}
{{ setBlock('title') ~}}Article Feed - MyVendor.Cms{{ endBlock() }}
{{ setBlock('header') ~}}<h1 class="ArticleFeed">Article Feed</h1>{{ endBlock() }}
<main>
  <section class="ArticleFeed">
    <?php foreach ($articles->feed($now) as $item): ?>
      <article class="ArticleFeedItem">
        <input type="hidden" class="id" value="{{h $item->id }}">
        <h2 class="title">
          <a class="goArticle" href="{{h $item->url }}">{{h $item->title }}</a>
        </h2>
        <time class="publishedAt" datetime="{{h $item->publishedAtLabel }}">{{h $item->publishedAtLabel }}</time>
        <span class="postedAgo">{{h $item->postedAgoLabel }}</span>
        <p class="summary">{{h $item->summary }}</p>
      </article>
    <?php endforeach ?>
  </section>
</main>
