<?php

namespace Alphasky\Table\BulkChanges;

use Alphasky\Table\Abstracts\TableBulkChangeAbstract;
use Closure;
use Illuminate\Validation\Rule;

class SelectBulkChange extends TableBulkChangeAbstract
{
    protected bool $searchable = false;

    protected Closure|array $choices;

    protected Closure $callback;

    public static function make(array $data = []): static
    {
        return parent::make()->type('customSelect');
    }

    public function searchable(bool $searchable = true): static
    {
        $this->searchable = $searchable;

        return $this;
    }

    public function choices(Closure|callable|array $choices): static
    {
        if (is_callable($choices)) {
            $this->choices = [];
            $this->callback = $choices;
        } else {
            $this->choices = $choices;
        }

        return $this;
    }

    public function toArray(): array
    {
        $data = parent::toArray();
        $choices = $this->choices ?? [];

        if (isset($this->callback)) {
            $data['callback'] = $this->callback;

            $callbackChoices = call_user_func($this->callback);
            if (is_array($callbackChoices)) {
                $choices = $callbackChoices;
            }
        } else {
            $data['choices'] = $this->choices;
        }

        if ($this->searchable) {
            $data['type'] = 'select-search';
        }

        if (! isset($this->validate)) {
            $allowedValues = array_keys($choices);

            // Support non-associative options arrays by validating against values.
            if ($allowedValues !== [] && $allowedValues === range(0, count($allowedValues) - 1)) {
                $allowedValues = array_values($choices);
            }

            $data['validate'] = ['required', Rule::in($allowedValues)];
        }

        return $data;
    }
}
