<?php

namespace App\Repositories\Profile;

use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use App\Repositories\Profile\GroupRepositoryInterface;

class GroupRepository implements GroupRepositoryInterface
{
    public function create(array $attrs): Group
    {
        return Group::create($attrs);
    }

    public function update(Group $group, array $attrs): Group
    {
        $group->update($attrs);
        return $group->refresh();
    }

    public function rename(Group $group, string $name): Group
    {
        $group->name = $name;
        $group->save();
        return $group->refresh();
    }

    public function members(Group $group): Collection
    {
        return $group->users()->orderBy('id')->get();
    }
}