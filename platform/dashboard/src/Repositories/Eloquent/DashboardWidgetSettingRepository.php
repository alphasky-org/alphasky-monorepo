<?php

namespace Alphasky\Dashboard\Repositories\Eloquent;

use Alphasky\Dashboard\Repositories\Interfaces\DashboardWidgetSettingInterface;
use Alphasky\Support\Repositories\Eloquent\RepositoriesAbstract;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class DashboardWidgetSettingRepository extends RepositoriesAbstract implements DashboardWidgetSettingInterface
{
    public function getListWidget(): Collection
    {
        $data = $this->model
            ->select([
                'id',
                'order',
                'settings',
                'widget_id',
            ])
            ->with('widget')
            ->orderBy('order')
            ->where('user_id', Auth::guard()->id())
            ->get();

        $this->resetModel();

        return $data;
    }
}
