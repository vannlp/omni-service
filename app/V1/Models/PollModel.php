<?php

namespace App\V1\Models;

use App\Poll;
use App\TM;
use Illuminate\Support\Facades\DB;

class PollModel extends AbstractModel
{
    public function __construct(Poll $model = null)
    {
        parent::__construct($model);
    }

    public function createPoll(array $attributes)
    {
        $userId      = TM::getCurrentUserId();
        $currentDate = date('Y-m-d H:i:s', time());

        try {
            DB::beginTransaction();

            // Create Poll
            $pollParams = [
                "company_id" => TM::getCurrentCompanyId(),
                "code"       => $attributes['code'],
                "name"       => $attributes['name'],
                "is_active"  => $attributes['is_active'] ?? 1,
                "created_by" => $userId,
                "updated_by" => $userId,
                "created_at" => $currentDate,
                "updated_at" => $currentDate,
            ];
            $poll       = $this->model->create($pollParams);

            foreach ($attributes['questions'] as $questionAttr) {
                $answerParams = [];

                // Create Questions
                $questionParams = [
                    "poll_id"    => $poll->id,
                    "title"      => $questionAttr['title'],
                    "is_active"  => $questionAttr['is_active'] ?? 1,
                    "created_by" => $userId,
                    "updated_by" => $userId,
                    "created_at" => $currentDate,
                    "updated_at" => $currentDate,
                ];
                $question       = $poll->questions()->create($questionParams);

                // Create Answers
                foreach ($questionAttr['answers'] as $answerAttr) {
                    $answerParams[] = [
                        "question_id" => $question->id,
                        "text"        => $answerAttr['text'],
                        "score"       => $answerAttr['score'],
                        "created_by"  => $userId,
                        "updated_by"  => $userId,
                        "created_at"  => $currentDate,
                        "updated_at"  => $currentDate,
                    ];
                }
                $question->answers()->createMany($answerParams);
            }

            DB::commit();

            return $poll;
        } catch (\Exception $exception) {
            DB::rollBack();
            throw $exception;
        }
    }

    public function updatePoll(array $attributes, $poll)
    {
        $userId      = TM::getCurrentUserId();
        $currentDate = date('Y-m-d H:i:s', time());

        try {
            DB::beginTransaction();

            // Update Poll
            $pollParams = [
                "code"       => $attributes['code'],
                "name"       => $attributes['name'],
                "is_active"  => $attributes['is_active'] ?? 1,
                "updated_by" => $userId,
                "updated_at" => $currentDate,
            ];
            $poll->update($pollParams);

            // Delete Question
            $pollQuestionIds = $poll->questions->pluck('id')->toArray();
            $attrQuestionIds = array_pluck($attributes['questions'], 'question_id');
            $idsRemove       = array_diff($pollQuestionIds, $attrQuestionIds);

            // Delete Answers
            $questionsRemove = $poll->questions()->whereIn('id', $idsRemove)->get();
            if (!empty($questionsRemove)) {
                foreach ($questionsRemove as $quesRemove) {
                    $quesRemove->answers()->delete();
                }
            }
            $poll->questions()->whereIn('id', $idsRemove)->delete();

            foreach ($attributes['questions'] as $questionAttr) {
                // Create or Update Questions
                $questionParams = [
                    "poll_id"    => $poll->id,
                    "title"      => $questionAttr['title'],
                    "is_active"  => $questionAttr['is_active'] ?? 1,
                    "updated_by" => $userId,
                    "updated_at" => $currentDate,
                ];
                if (!empty($questionAttr['question_id'])) {
                    $question = $poll->questions()->with('answers')->find($questionAttr['question_id']);
                    $question->update($questionParams);

                    // Delete Answers
                    $answerIds       = $question->answers->pluck('id')->toArray();
                    $attrAnswerIds   = array_pluck($questionAttr['answers'], 'answer_id');
                    $answerIdsRemove = array_diff($answerIds, $attrAnswerIds);
                    $question->answers()->whereIn('id', $answerIdsRemove)->delete();

                    // Create or Update Answers
                    foreach ($questionAttr['answers'] as $answerAttr) {
                        $answerParams = [
                            "question_id" => $question->id,
                            "text"        => $answerAttr['text'],
                            "score"       => $answerAttr['score'],
                            "updated_by"  => $userId,
                            "updated_at"  => $currentDate,
                        ];

                        if (!empty($answerAttr['answer_id'])) {
                            $question->answers()->find($answerAttr['answer_id'])->update($answerParams);
                        } else {
                            $questionParams['created_by'] = $userId;
                            $questionParams['created_at'] = $currentDate;
                            $question->answers()->create($answerParams);
                        }
                    }
                } else {
                    $answerParams = [];

                    // Create Question
                    $questionParams['created_by'] = $userId;
                    $questionParams['created_at'] = $currentDate;
                    $question                     = $poll->questions()->create($questionParams);

                    // Create Answers
                    foreach ($questionAttr['answers'] as $answerAttr) {
                        $answerParams[] = [
                            "question_id" => $question->id,
                            "text"        => $answerAttr['text'],
                            "score"       => $answerAttr['score'],
                            "created_by"  => $userId,
                            "updated_by"  => $userId,
                            "created_at"  => $currentDate,
                            "updated_at"  => $currentDate,
                        ];
                    }
                    $question->answers()->createMany($answerParams);
                }
            }

            DB::commit();

            return $poll;
        } catch (\Exception $exception) {
            DB::rollBack();
            throw $exception;
        }
    }

    public function perform(array $attributes, $poll)
    {
        $currentDate = date('Y-m-d H:i:s', time());
        $user        = TM::info();

        try {
            DB::beginTransaction();

            $score       = 0;
            $attrDetails = $attributes['details'];

            foreach ($attrDetails as $item) {
                if (empty($item['answer_id'])) {
                    continue;
                }
                $question = $poll->questions()->with('answers')->find($item['question_id']);
                $answer   = $question->answers()->find($item['answer_id']);
                $score    += $answer->score;
            }

            $performParam = [
                'object_name'  => $user['name'],
                'object_code'  => $user['code'],
                'score'        => $score,
                'details_json' => json_encode($attrDetails),
                'created_at'   => $currentDate,
                'updated_at'   => $currentDate,
            ];
            $poll->performers()->create($performParam);

            DB::commit();

            return true;
        } catch (\Exception $exception) {
            DB::rollBack();
            throw $exception;
        }
    }
}
