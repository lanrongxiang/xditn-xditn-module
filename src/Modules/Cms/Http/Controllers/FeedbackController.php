<?php

declare(strict_types=1);

namespace Modules\Cms\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Cms\Models\Feedback;
use XditnModule\Base\CatchController as Controller;

/**
 * @group 管理端
 *
 * @subgroup 反馈管理
 *
 * @subgroupDescription 用户反馈管理
 */
class FeedbackController extends Controller
{
    public function __construct(
        protected readonly Feedback $model
    ) {
    }

    /**
     * 反馈列表.
     *
     * @queryParam type string 反馈类型：bug、suggestion、other
     * @queryParam status string 状态：pending、processing、resolved、closed
     * @queryParam user_id int 用户ID
     * @queryParam page int 页码
     * @queryParam limit int 每页数量
     *
     * @responseField data object[] 反馈列表
     * @responseField data[].id int 反馈ID
     * @responseField data[].user_id int 用户ID
     * @responseField data[].type string 反馈类型
     * @responseField data[].title string 反馈标题
     * @responseField data[].content string 反馈内容
     * @responseField data[].contact string 联系方式
     * @responseField data[].images array 反馈图片
     * @responseField data[].status string 状态
     * @responseField data[].reply string 管理员回复
     * @responseField data[].replied_at int 回复时间
     * @responseField data[].created_at int 创建时间
     *
     * @return mixed
     */
    public function index(): mixed
    {
        return $this->model->getList();
    }

    /**
     * 反馈详情.
     *
     * @urlParam id int required 反馈ID
     *
     * @responseField data object 反馈详情
     *
     * @param int $id
     *
     * @return mixed
     */
    public function show(int $id): mixed
    {
        return $this->model->firstBy($id);
    }

    /**
     * 回复反馈.
     *
     * @urlParam id int required 反馈ID
     *
     * @bodyParam reply string required 回复内容
     * @bodyParam status string 状态：processing、resolved、closed
     *
     * @responseField data object 反馈信息
     *
     * @param Request $request
     * @param int $id
     *
     * @return mixed
     */
    public function reply(Request $request, int $id): mixed
    {
        $feedback = $this->model->firstBy($id);
        if (!$feedback) {
            throw new \XditnModule\Exceptions\FailedException('反馈不存在');
        }

        $data = [
            'reply' => $request->input('reply'),
            'status' => $request->input('status', $feedback->status),
            'replied_at' => time(),
            'replied_by' => $this->getLoginUserId(),
        ];

        return $this->model->updateBy($id, $data);
    }

    /**
     * 更新反馈状态
     *
     * @urlParam id int required 反馈ID
     *
     * @bodyParam status string required 状态：pending、processing、resolved、closed
     *
     * @responseField data object 反馈信息
     *
     * @param Request $request
     * @param int $id
     *
     * @return mixed
     */
    public function updateStatus(Request $request, int $id): mixed
    {
        $status = $request->input('status');
        if (!in_array($status, ['pending', 'processing', 'resolved', 'closed'])) {
            throw new \XditnModule\Exceptions\FailedException('状态值不正确');
        }

        return $this->model->updateBy($id, ['status' => $status]);
    }

    /**
     * 删除反馈.
     *
     * @urlParam id int required 反馈ID
     *
     * @responseField data bool 是否成功
     *
     * @param int $id
     *
     * @return mixed
     */
    public function destroy(int $id): mixed
    {
        return $this->model->deleteBy($id);
    }
}
