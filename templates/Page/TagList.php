<?php
/**
 * @var list<\MyVendor\Cms\Entity\Tag> $tags
 */
?>
{{ setLayout('layout/Default') }}
{{ setBlock('bodyClass') ~}}public public-list public-taglist{{ endBlock() }}
{{ setBlock('title') ~}}Tag List - MyVendor.Cms{{ endBlock() }}
{{ setBlock('header') ~}}<h1 class="TagList">Tags</h1>{{ endBlock() }}
<main>
  <section class="TagList">
    <?php if ($tags === []): ?>
      <p>No tags defined yet.</p>
    <?php endif ?>
    <ul>
      <?php foreach ($tags as $tag): ?>
        <li class="Tag">
          <input type="hidden" class="id" value="{{h $tag->id }}">
          <a class="goTag" href="/tag?id={{h $tag->id }}">
            <span class="name">{{h $tag->name }}</span>
            <span class="slug">{{h $tag->slug }}</span>
          </a>
        </li>
      <?php endforeach ?>
    </ul>
  </section>
</main>
