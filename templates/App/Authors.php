<?php
/**
 * App resource HTML representation.
 *
 * Reachability: App resources hold state only; the representation is bound
 * from the outside by context. Under html-hal-app this template renders the
 * author collection as an HTML fragment; under hal-api-app the same resource
 * is rendered as HAL/JSON by HalRenderer. The resource itself is unchanged.
 *
 * @var list<array{id: int, name: string, email: string, bio: string}> $items
 */
?>
<ul class="AuthorList">
<?php foreach ($items as $author): ?>
  <li class="Author">
    <a class="goAuthor" href="/author?id={{h $author['id'] }}">{{h $author['name'] }}</a>
    <?php if ($author['email'] !== ''): ?><span class="email">{{h $author['email'] }}</span><?php endif ?>
  </li>
<?php endforeach ?>
</ul>
