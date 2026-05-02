{{ setLayout('layout/Default') }}
{{ setBlock('title') ~}}Admin - BEAR.Cms Reference CMS{{ endBlock() }}
{{ setBlock('header') ~}}<h1 class="AdminIndex">Admin</h1>{{ endBlock() }}
<main>
  <section class="AdminIndex">
    <h2>Content</h2>
    <ul>
      <li><a href="/admin/articlelist" class="goAdminArticleList">Article administration</a></li>
    </ul>
  </section>
</main>
<nav>
  <h2>Navigation</h2>
  <ul>
    <li><a href="/articlelist" class="goArticleList">Public article list</a></li>
    <li><a href="/" class="goIndex">Home</a></li>
  </ul>
</nav>
