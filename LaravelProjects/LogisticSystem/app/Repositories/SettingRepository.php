<?php

namespace App\Repositories;

use App\Models\Setting;
use Illuminate\Support\Collection;

class SettingRepository extends BaseRepository
{
    public function __construct(Setting $setting)
    {
        parent::__construct($setting);
    }

    public function model(): Setting
    {
        return new Setting();
    }

    public function findByKey(string $key): ?Setting
    {
        return $this->findBy('key', $key);
    }

    public function findByGroup(string $group): Collection
    {
        return $this->findAllBy('group', $group);
    }
}
