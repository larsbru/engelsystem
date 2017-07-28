<?php

use Engelsystem\Database\DB;

/**
 * @return string
 */
function questions_title()
{
    return _('Ask the Heaven');
}

/**
 * @return string
 */
function user_questions()
{
    global $user;
    $request = request();

    if (!$request->has('action')) {
        $open_questions = DB::select(
            'SELECT * FROM `Questions` WHERE `AID` IS NULL AND `UID`=?',
            [$user['UID']]
        );

        $answered_questions = DB::select(
            'SELECT * FROM `Questions` WHERE NOT `AID` IS NULL AND `UID`=?',
            [$user['UID']]
        );
        foreach ($answered_questions as &$question) {
            $answer_user_source = User($question['AID']);
            $question['answer_user'] = User_Nick_render($answer_user_source);
        }

        return Questions_view($open_questions, $answered_questions, page_link_to('user_questions') . '&action=ask');
    } else {
        switch ($request->input('action')) {
            case 'ask':
                $question = strip_request_item_nl('question');
                if ($question != '') {
                    DB::insert('
                        INSERT INTO `Questions` (`UID`, `Question`)
                        VALUES (?, ?)
                        ',
                        [$user['UID'], $question]
                    );

                    success(_('You question was saved.'));
                    redirect(page_link_to('user_questions'));
                } else {
                    return page_with_title(questions_title(), [
                        error(_('Please enter a question!'), true)
                    ]);
                }
                break;
            case 'delete':
                if ($request->has('id') && preg_match('/^\d{1,11}$/', $request->input('id'))) {
                    $question_id = $request->input('id');
                } else {
                    return error(_('Incomplete call, missing Question ID.'), true);
                }

                $question = DB::selectOne(
                    'SELECT `UID` FROM `Questions` WHERE `QID`=? LIMIT 1',
                    [$question_id]
                );
                if (!empty($question) && $question['UID'] == $user['UID']) {
                    DB::delete(
                        'DELETE FROM `Questions` WHERE `QID`=? LIMIT 1',
                        [$question_id]
                    );
                    redirect(page_link_to('user_questions'));
                } else {
                    return page_with_title(questions_title(), [
                        error(_('No question found.'), true)
                    ]);
                }
                break;
        }
    }

    return '';
}
