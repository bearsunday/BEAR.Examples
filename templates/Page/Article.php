<?php
/**
 * @var \BEAR\Kata\Entity\Article|null $article
 * @var string $bodyHtml
 * @var \BEAR\Kata\Entity\Author|null $author
 * @var \BEAR\Kata\Entity\Category|null $category
 * @var list<array{id:int, slug:string, name:string}> $tags
 */
if (! isset($article) || $article === null) {
    throw new \BEAR\Kata\Exception\ArticleNotFoundException();
}
?>
{{ setLayout('layout/Default') }}
{{ setBlock('bodyClass') ~}}public public-detail public-article{{ endBlock() }}
{{ setBlock('title') ~}}{{h $article->title }} - BEAR.Kata{{ endBlock() }}
{{ setBlock('header') ~}}<h1 class="Article">Article Detail</h1>{{ endBlock() }}
<main>
  <article class="Article">
    <input type="hidden" class="id" value="{{h $article->id }}">
    <span class="slug">{{h $article->slug }}</span>
    <h2 class="title">{{h $article->title }}</h2>
    <span class="status" data-status="{{h $article->status->value }}">{{h $article->status->value }}</span>
    <?php $publishedAtLabel = $article->publishedAtLabel(); ?>
    <?php if ($publishedAtLabel !== null): ?>
      <time class="publishedAt" datetime="{{h $publishedAtLabel }}">{{h $publishedAtLabel }}</time>
    <?php endif ?>
    <?php $summary = $article->summary(); ?>
    <?php if ($summary !== null): ?>
      <p class="excerpt">{{h $summary }}</p>
    <?php endif ?>
    <div class="body">{{= $bodyHtml }}</div>
  </article>

  <aside class="ArticleMeta">
    <?php if ($author !== null): ?>
      <section class="Author">
        <input type="hidden" class="id" value="{{h $author->id }}">
        <span class="role">Written by</span>
        <span class="name">{{h $author->name }}</span>
        <a class="email" href="mailto:{{h $author->email }}">{{h $author->email }}</a>
      </section>
    <?php endif ?>

    <?php if ($category !== null): ?>
      <section class="Category">
        <input type="hidden" class="id" value="{{h $category->id }}">
        <span class="role">Category</span>
        <a class="goCategory" href="/category?id={{h $category->id }}">
          <span class="name">{{h $category->name }}</span>
          <span class="slug">{{h $category->slug }}</span>
        </a>
      </section>
    <?php endif ?>

    <?php if ($tags !== []): ?>
      <section class="TagSet">
        <span class="role">Tags</span>
        <?php foreach ($tags as $tag): ?>
          <section class="Tag">
            <input type="hidden" class="id" value="{{h $tag['id'] }}">
            <a class="goTag" href="/tag?id={{h $tag['id'] }}">
              <span class="name">{{h $tag['name'] }}</span>
              <span class="slug">{{h $tag['slug'] }}</span>
            </a>
          </section>
        <?php endforeach ?>
      </section>
    <?php endif ?>
  </aside>
</main>
<?php if ($author !== null || $category !== null): ?>
<nav class="Related">
  <ul>
    <?php if ($author !== null): ?>
      <li><a href="/author?id={{h $author->id }}" class="goAuthor" title="Navigate to the author detail.">By {{h $author->name }}</a></li>
    <?php endif ?>
    <?php if ($category !== null): ?>
      <li><a href="/category?id={{h $category->id }}" class="goCategory" title="Navigate to the category detail.">In {{h $category->name }}</a></li>
    <?php endif ?>
  </ul>
</nav>
<?php endif ?>
