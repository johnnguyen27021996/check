<?php


namespace App\Services\Admin;


use App\Models\TipHistory;
use App\Services\BaseService;
use Illuminate\Database\Eloquent\Builder;

class TipService extends BaseService
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function setModel()
    {
        $this->model = new TipHistory();
    }

    public function getListTip($request)
    {
        $tips = $this->model->query()
            ->with('user', 'lesson', 'lesson.program', 'receive_user')
            ->when($user_id = $request->user_id, function (Builder $builder, $user_id) {
                return $builder->where('user_id', $user_id);
            })
            ->when($user_nickname = $request->user_nickname, function (Builder $builder, $user_nickname) {
                return $builder->whereHas('user', function (Builder $builder) use ($user_nickname) {
                    return $builder->where('nickname', 'like', '%'.$user_nickname.'%');
                });
            })
            ->when($receive_user_id = $request->receive_user_id, function (Builder $builder, $receive_user_id) {
                return $builder->where('receive_user_id', $receive_user_id);
            })
            ->when($receive_user_nickname = $request->receive_user_nickname, function (Builder $builder, $receive_user_nickname) {
                return $builder->whereHas('receive_user', function (Builder $builder) use ($receive_user_nickname) {
                    return $builder->where('nickname', 'like', '%'.$receive_user_nickname.'%');
                });
            })
            ->when($lesson_id = $request->lesson_id, function (Builder $builder, $lesson_id) {
                return $builder->where('lesson_id', $lesson_id);
            })
            ->when($lesson_name = $request->lesson_name, function (Builder $builder, $lesson_name) {
                return $builder->whereHas('lesson', function (Builder $builder) use ($lesson_name) {
                    return $builder->where('name', 'like', '%'.$lesson_name.'%');
                });
            });

        if($request->sort) {
            $sort = $request->sort;
            $sortName = explode('|', $sort)[0];
            $sortType = explode('|', $sort)[1];
            switch ($sortName) {
                case 'user':
                    $tips
                        ->join('users', 'users.id', '=', 'tip_history.user_id')
                        ->select('tip_history.*','users.nickname')
                        ->orderBy('nickname', $sortType);
                    break;
                case 'receive_user':
                    $tips
                        ->join('users', 'users.id', '=', 'tip_history.receive_user_id')
                        ->select('tip_history.*','users.nickname')
                        ->orderBy('nickname', $sortType);
                    break;
                default:
                    $tips->orderBy($sortName, $sortType);
            }

        }

        return $this->getPaginateByQuery($tips, $request);
    }
}
