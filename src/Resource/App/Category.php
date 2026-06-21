<?php

declare(strict_types=1);

namespace BEAR\Kata\Resource\App;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\RepositoryModule\Annotation\Purge;
use BEAR\Resource\Annotation\JsonSchema;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use BEAR\Kata\Query\CategoryCommandInterface;
use BEAR\Kata\Query\CategoryQueryInterface;

use function assert;

#[Alps('Category')]
class Category extends ResourceObject
{
    public function __construct(
        private readonly CategoryQueryInterface $category,
        private readonly CategoryCommandInterface $categoryCmd,
    ) {
    }

    #[Alps('goCategory')]
    #[Link(rel: 'goCategoryList', href: 'app://self/categories')]
    #[Link(rel: 'goArticleList', href: 'app://self/articles{?categoryId}')]
    #[JsonSchema('category.json')]
    public function onGet(int $id): static
    {
        $category = $this->category->item($id);
        if ($category === null) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['message' => 'Category not found', 'id' => $id];

            return $this;
        }

        $this->body = [
            'id' => $category->id,
            'slug' => $category->slug,
            'name' => $category->name,
            'description' => $category->description,
            'parentId' => $category->parentId,
        ];

        return $this;
    }

    #[Alps('doCreateCategory')]
    #[JsonSchema(schema: 'write_response.json', params: 'category_create.json')]
    #[Purge(uri: 'app://self/categories')]
    public function onPost(
        string $slug,
        string $name,
        string|null $description = null,
        int|null $parentId = null,
    ): static {
        $this->categoryCmd->add($slug, $name, $description, $parentId);
        // bySlug after add is invariant per docs/conventions.md §4.
        $created = $this->category->bySlug($slug);
        assert($created !== null);
        $this->code = Code::CREATED;
        $this->headers['Location'] = '/category?id=' . $created->id;
        $this->body = ['id' => $created->id, 'slug' => $slug];

        return $this;
    }

    #[Alps('doUpdateCategory')]
    #[JsonSchema(schema: 'write_response.json', params: 'category_update.json')]
    #[Purge(uri: 'app://self/categories')]
    public function onPut(
        int $id,
        string $name,
        string|null $description = null,
        int|null $parentId = null,
    ): static {
        if ($this->category->item($id) === null) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['message' => 'Category not found', 'id' => $id];

            return $this;
        }

        $this->categoryCmd->update($id, $name, $description, $parentId);
        $this->code = Code::OK;
        $this->body = ['id' => $id];

        return $this;
    }

    #[Alps('doDeleteCategory')]
    #[Purge(uri: 'app://self/categories')]
    public function onDelete(int $id): static
    {
        if ($this->category->item($id) === null) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['message' => 'Category not found', 'id' => $id];

            return $this;
        }

        $this->categoryCmd->delete($id);
        $this->code = Code::NO_CONTENT;
        $this->body = [];

        return $this;
    }
}
