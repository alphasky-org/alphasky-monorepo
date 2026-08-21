<?php

namespace Alphasky\Table\Columns;

use Alphasky\Table\Contracts\FormattedColumn as FormattedColumnContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class ListColumn extends FormattedColumn implements FormattedColumnContract
{
    protected ?string $modelClass = null;

    protected $resolverFunction = null;

    protected string $valueColumn = 'name';

    protected string $keyColumn = 'id';

    protected string $separator = ', ';

    protected array $resolvedItems = [];

    public static function make(array|string $data = [], string $name = ''): static
    {
        return parent::make($data ?: 'list', $name);
           
    }

    /**
     * @param class-string<Model> $modelClass
     */
    public function fromModel(string $modelClass, string $valueColumn, string $keyColumn = 'id'): static
    {
        $this->modelClass = $modelClass;
        $this->resolverFunction = null;
        $this->valueColumn = $valueColumn;
        $this->keyColumn = $keyColumn;

        return $this;
    }

    public function fromFunction(callable|string|array $resolverFunction, string $valueColumn = 'name', string $keyColumn = 'id'): static
    {
        $this->resolverFunction = $resolverFunction;
        $this->modelClass = null;
        $this->valueColumn = $valueColumn;
        $this->keyColumn = $keyColumn;

        return $this;
    }

    public function separator(string $separator): static
    {
        $this->separator = $separator;

        return $this;
    }

    public function formattedValue($value): ?string
    {
        if (! $value) {
            return '';
        }

        $ids = $this->normalizeToArray($value);

        if (! $ids) {
            return '';
        }

        if ($this->resolverFunction) {
            $mappedItems = $this->resolveMappedItemsFromFunction($ids);

            return $this->renderTags($mappedItems ?: $this->mapFallbackItems($ids));
        }

        if (! $this->modelClass || ! class_exists($this->modelClass)) {
            return $this->renderTagsFromIds($ids);
        }

        $mappedItems = $this->resolveMappedItems($ids);

        return $this->renderTags($mappedItems ?: $this->mapFallbackItems($ids));
    }

    protected function normalizeToArray(mixed $value): array
    {
        if ($value instanceof Collection) {
            $value = $value->all();
        }

        if (is_array($value)) {
            return array_values(array_filter(array_map(fn ($item) => trim((string) $item), $value), fn ($item) => $item !== ''));
        }

        if (! is_string($value)) {
            return [];
        }

        $trimmed = trim($value);

        if ($trimmed === '') {
            return [];
        }

        $decoded = json_decode($trimmed, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return array_values(array_filter(array_map(fn ($item) => trim((string) $item), $decoded), fn ($item) => $item !== ''));
        }

        return array_values(array_filter(array_map('trim', explode(',', $trimmed)), fn ($item) => $item !== ''));
    }

    protected function resolveMappedItems(array $ids): array
    {
        $normalizedIds = array_values(array_unique(array_map(fn ($id) => (string) $id, $ids)));
        $missingIds = array_values(array_diff($normalizedIds, array_keys($this->resolvedItems)));

        if ($missingIds) {
            $queryResults = $this->modelClass::query()
                ->whereIn($this->keyColumn, $missingIds)
                ->pluck($this->valueColumn, $this->keyColumn)
                ->all();

            foreach ($queryResults as $key => $label) {
                $this->resolvedItems[(string) $key] = (string) $label;
            }
        }

        $resolved = [];

        foreach ($ids as $id) {
            $key = (string) $id;

            if (isset($this->resolvedItems[$key])) {
                $resolved[] = [
                    'id' => $key,
                    'label' => $this->resolvedItems[$key],
                ];
            }
        }

        return $resolved;
    }

    protected function resolveMappedItemsFromFunction(array $ids): array
    {
        $allItems = $this->resolveAllItemsFromFunction();

        if (! $allItems) {
            return [];
        }

        $indexed = [];

        foreach ($allItems as $id => $label) {
            $indexed[(string) $id] = (string) $label;
        }

        $resolved = [];

        foreach ($ids as $id) {
            $key = (string) $id;

            if (array_key_exists($key, $indexed)) {
                $resolved[] = [
                    'id' => $key,
                    'label' => $indexed[$key],
                ];
            }
        }

        return $resolved;
    }

    protected function resolveAllItemsFromFunction(): array
    {
        if (is_array($this->resolverFunction) && $this->isAssocArray($this->resolverFunction)) {
            return $this->resolverFunction;
        }

        $resolved = [];

        if (is_callable($this->resolverFunction)) {
            $resolved = call_user_func($this->resolverFunction);
        } elseif (is_string($this->resolverFunction) && function_exists($this->resolverFunction)) {
            $resolved = call_user_func($this->resolverFunction);
        }

        if (! is_array($resolved)) {
            return [];
        }

        if ($this->isAssocArray($resolved)) {
            return $resolved;
        }

        $mapped = [];

        foreach ($resolved as $item) {
            if (is_array($item)) {
                $id = $item[$this->keyColumn] ?? null;
                $label = $item[$this->valueColumn] ?? null;
            } elseif (is_object($item)) {
                $id = $item->{$this->keyColumn} ?? null;
                $label = $item->{$this->valueColumn} ?? null;
            } else {
                continue;
            }

            if ($id === null || $label === null) {
                continue;
            }

            $mapped[(string) $id] = (string) $label;
        }

        return $mapped;
    }

    protected function isAssocArray(array $array): bool
    {
        if ($array === []) {
            return false;
        }

        return array_keys($array) !== range(0, count($array) - 1);
    }

    protected function mapFallbackItems(array $ids): array
    {
        return array_map(fn ($id) => [
            'id' => (string) $id,
            'label' => (string) $id,
        ], $ids);
    }

    protected function renderTagsFromIds(array $ids): string
    {
        return $this->renderTags($this->mapFallbackItems($ids));
    }

    protected function renderTags(array $items): string
    {
        $tags = [];

        foreach ($items as $item) {
            $id = (string) ($item['id'] ?? '');
            $label = e((string) ($item['label'] ?? ''));

            if ($id === '' || $label === '') {
                continue;
            }

            $classId = preg_replace('/[^A-Za-z0-9_-]/', '', $id);
            $tags[] = '<span class="badge bg-secondary text-secondary-fg v' . $classId . '">' . $label . '</span>';
        }

        return implode(' ', $tags);
    }
}
