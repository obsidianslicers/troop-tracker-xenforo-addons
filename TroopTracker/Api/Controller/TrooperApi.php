<?php

namespace ObsidianSlicers\TroopTracker\Api\Controller;

use XF\Mvc\ParameterBag;
use XF\Api\Mvc\Reply\ApiResult as ApiResultReply;

/**
 * TroopTracker Mobile API controller
 *
 * Public endpoints – caller must be logged in via a standard XF session:
 *
 *   GET  /api/trooper-api/block-user?blocked_id=Y
 *   GET  /api/trooper-api/report-post?post_id=X&message=...
 *
 * The acting user is always \XF::visitor() (the authenticated session user
 * or the API user resolved from XF-Api-Key / XF-Api-User headers).
 */
class TrooperApi extends \XF\Api\Controller\AbstractController
{
    public function checkCsrfIfNeeded($action, ParameterBag $params): void
    {
        // intentionally left empty — mobile clients won't have a CSRF token
    }

    protected function preDispatchController($action, ParameterBag $params): void
    {
        parent::preDispatchController($action, $params);

        if (!\XF::visitor()->user_id) {
            throw $this->exception(
                $this->apiResult(['error' => 'You must be logged in.'])
            );
        }
    }

    public function actionBlockUser(ParameterBag $params): ApiResultReply
    {
        $visitor       = \XF::visitor();
        $blockerUserId = $visitor->user_id;
        $blockedUserId = $this->filter('blocked_id', 'uint');

        if (!$blockedUserId) {
            return $this->apiResult(['error' => 'blocked_id is required.']);
        }

        if ($blockerUserId === $blockedUserId) {
            return $this->apiResult(['error' => 'You cannot block yourself.']);
        }

        $blocked = $this->em()->find('XF:User', $blockedUserId);
        if (!$blocked) {
            return $this->apiResult(['error' => 'User not found.']);
        }

        try {
            $ignoredEntity = $this->em()->findOne('XF:UserIgnored', [
                'user_id'         => $blockerUserId,
                'ignored_user_id' => $blockedUserId,
            ]);

            if ($ignoredEntity) {
                $ignoredEntity->delete();
                return $this->apiResult(['message' => 'User unblocked successfully.', 'blocked' => false]);
            }

            $ignoredEntity                  = $this->em()->create('XF:UserIgnored');
            $ignoredEntity->user_id         = $blockerUserId;
            $ignoredEntity->ignored_user_id = $blockedUserId;
            $ignoredEntity->save();

            return $this->apiResult(['message' => 'User blocked successfully.', 'blocked' => true]);
        } catch (\Exception $e) {
            \XF::logException($e);
            return $this->apiResult(['error' => 'Failed to block user.']);
        }
    }

    public function actionGetBlockUser(ParameterBag $params): ApiResultReply
    {
        return $this->actionBlockUser($params);
    }

    public function actionReportPost(ParameterBag $params): ApiResultReply
    {
        $postId  = $this->filter('post_id', 'uint');
        $message = $this->filter('message', 'str') ?: 'No reason provided.';

        if (!$postId) {
            return $this->apiResult(['error' => 'post_id is required.']);
        }

        $post = $this->em()->find('XF:Post', $postId);
        if (!$post) {
            return $this->apiResult(['error' => 'Post not found.']);
        }

        try {
            /** @var \XF\Service\Report\Creator $creator */
            $creator = $this->service('XF:Report\Creator', 'post', $post);
            $creator->setMessage($message);

            if (!$creator->validate($errors)) {
                return $this->apiResult(['error' => 'Validation failed.', 'details' => $errors]);
            }

            $report = $creator->save();

            if ($report) {
                if ($report->report_state !== 'open') {
                    $report->report_state = 'open';
                    $report->save();
                }

                return $this->apiResult([
                    'message'      => 'Post reported successfully.',
                    'report_id'    => $report->report_id,
                    'report_state' => $report->report_state,
                ]);
            }

            return $this->apiResult(['error' => 'Failed to report post.']);
        } catch (\Exception $e) {
            \XF::logException($e);
            return $this->apiResult(['error' => 'Failed to report post.']);
        }
    }

    public function actionGetReportPost(ParameterBag $params): ApiResultReply
    {
        return $this->actionReportPost($params);
    }

    public function actionPostWatchThread(ParameterBag $params): ApiResultReply
    {
        $this->assertApiScope('thread:write');

        $threadId       = $this->filter('thread_id', 'uint');
        $emailSubscribe = $this->filter('email_subscribe', 'bool');

        if (!$threadId) {
            return $this->apiResult(['error' => 'thread_id is required.']);
        }

        $thread = $this->em()->find('XF:Thread', $threadId);
        if (!$thread) {
            return $this->apiResult(['error' => 'Thread not found.']);
        }

        $state = $emailSubscribe ? 'watch_email' : 'watch_no_email';

        /** @var \XF\Repository\ThreadWatchRepository $repo */
        $repo = $this->repository('XF:ThreadWatch');
        $repo->setWatchState($thread, \XF::visitor(), $state);

        return $this->apiResult(['success' => true, 'watching' => true, 'email_subscribe' => $emailSubscribe]);
    }

    public function actionDeleteWatchThread(ParameterBag $params): ApiResultReply
    {
        $this->assertApiScope('thread:write');

        $threadId = $this->filter('thread_id', 'uint');

        if (!$threadId) {
            return $this->apiResult(['error' => 'thread_id is required.']);
        }

        $thread = $this->em()->find('XF:Thread', $threadId);
        if (!$thread) {
            return $this->apiResult(['error' => 'Thread not found.']);
        }

        /** @var \XF\Repository\ThreadWatchRepository $repo */
        $repo = $this->repository('XF:ThreadWatch');
        $repo->setWatchState($thread, \XF::visitor(), 'delete');

        return $this->apiResult(['success' => true, 'watching' => false]);
    }
}
