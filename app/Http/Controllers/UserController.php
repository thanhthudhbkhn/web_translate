<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImproveMeaningRequest;
use App\Http\Requests\AddKeywordRequest;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Meaning;
use App\Keyword;
use App\KeywordTemp;
use App\MeaningTemp;
use Illuminate\Support\Facades\DB;
use Cartalyst\Sentinel\Laravel\Facades\Sentinel;
use Validator;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\User;

class UserController extends Controller {

    // protected $user;
    // public function __construct(UserRepositoryInterface $user)
    // {
    //     $this->user = $user;
    // }

    public function view() {
        $user = Sentinel::getUser();
        return view('user.view')->with('user', $user);
    }

    public function edit() {
        $user = Sentinel::getUser();
        return view('user.edit')->with('user', $user);
    }

    public function update(\App\Http\Requests\CheckUpdateInfoRequest $request) {
        try {
            $user = Sentinel::getUser();
            $user->first_name = $request->first_name;
            $user->last_name = $request->last_name;

            DB::beginTransaction();
            $user->update();
            DB::commit();

            $notification = array(
                'message' => 'You have successfully update your information.',
                'alert-type' => 'success'
            );
        } catch (\Exception $e) {
            DB::rollback();
            $notification = array(
                'message' => 'Something went wrong.',
                'alert-type' => 'error'
            );
            return redirect('user/edit')->with($notification);
        }
        return redirect('user/edit')->with($notification);
    }

    public function addKeyword() {
        return view('user.keywordAdd');
    }

    public function processAddKeyword(AddKeywordRequest $request) {
        try {
            $user = Sentinel::getUser();
            $dataMeaning = array();

            DB::beginTransaction();
            //save keyword in keyword table to get id
            $keyword = Keyword::create([
                        'keyword' => $request->keyword,
                        'status' => IN_QUEUE
            ]);
            //save keyword in keywordTemp table 
            KeywordTemp::create([
                        'opCode' => ADD,
                        'user_id' => $user->id,
                        'old_keyword_id' => $keyword->id,
                        'new_keyword' => $request->keyword,
                        'comment' => $request->comment,
                        'status' => IN_QUEUE
            ]);
            //save meaning in meaningTemp table
            foreach ($request->translate as $key => $value) {
                $dataMeaning[] = array(
                    'opCode' => ADD,
                    'user_id' => $user->id,
                    'keyword_id' => $keyword->id,
                    'new_meaning' => $value['meaning'],
                    'index' => $key,
                    'language' => $value['language'],
                    'comment' => $request->comment,
                    'type' => $value['type'],
                    'status' => IN_QUEUE
                );
            }
            foreach ($dataMeaning as $key => $value) {
                MeaningTemp::create($value);
            }
            DB::commit();

            $notification = array(
                'message' => 'You have successfully contribute new keyword.',
                'alert-type' => 'success'
            );
        } catch (\Exception $e) {
            DB::rollback();
            $notification = array(
                'message' => 'Something went wrong.',
                'alert-type' => 'error'
            );
            return redirect('user/add/keyword')->with($notification);
        }
        return redirect('user/history')->with($notification);
    }

    public function showContributeHistory() {
        $user = Sentinel::getUser();
        $dataKeyword = KeywordTemp::where('user_id', $user->id)->get();
        $dataMeaning = MeaningTemp::where('user_id', $user->id)->get();
        return view('user.contributeHistory', ['dataKeyword' => $dataKeyword, 'dataMeaning' => $dataMeaning]);
    }

    public function deleteKeywordContribute($id) {
        //delete in wt_keyword_temp, wt_meaning_temp and wt_keyword
            try {
                $keywordtmp = KeywordTemp::find($id);
                $keyword = Keyword::find($keywordtmp->old_keyword_id);
                $meaningtmps = MeaningTemp::where('keyword_id', $keyword->id);

                DB::beginTransaction();
                $keywordtmp->delete();
                foreach ($meaningtmps as $meaningtmp) {
                    $meaningtmp->delete();
                }
                //if this keyword is added by user
                if ($keyword->status == IN_QUEUE) {
                    $keyword->forceDelete();
                }
                DB::commit();
                $notification = array(
                    'message' => 'You have been canceled a pending contribtute successfully',
                    'alert-type' => 'success'
                );
            } catch (\Exception $e) {
                DB::rollback();
                $notification = array(
                    'message' => 'Something went wrong.',
                    'alert-type' => 'error'
                );
            }
        return redirect('user/history')->with($notification);
    }

    public function deleteMeaningContribute($id) {
        //delete in wt_meaning_temp and delete keyword if there are not any meaning
        try {
            $meaningtmp = MeaningTemp::find($id);
            $opCode = $meaningtmp->opCode;
            $keywordtmp = KeywordTemp::where('old_keyword_id', $meaningtmp->keyword_id);
            $keyword = Keyword::find($meaningtmp->keyword_id);

            DB::beginTransaction();
            $meaningtmp->delete();
            
            if($opCode == ADD) {
                $numberOfMeanings = MeaningTemp::where('keyword_id', $meaningtmp->keyword_id)->count();
                //delete keyword            
                if ($numberOfMeanings == 0) {
                    $keywordtmp->delete();
                    if ($keyword->status == IN_QUEUE) {
                        $keyword->forceDelete();
                    }
                }
            }
            DB::commit();
            
            $notification = array(
                'message' => 'You have been canceled a pending contribtute successfully',
                'alert-type' => 'success'
            );
        } catch (\Exception $e) {
            DB::rollback();
            $notification = array(
                'message' => 'Something went wrong.',
                'alert-type' => 'error'
            );
        }
        return redirect('user/history')->with($notification);
    }
    
    public function editMeaningContribute($id) {

        $meaningtmp = MeaningTemp::find($id);
        return view('user.contributeMeaningEdit', ['meaningtmp' => $meaningtmp]);
    }

    public function processEditMeaningContribute(\App\Http\Requests\EditMeaningRequest $request) {
        //eidt in wt_meaning_temp
        $id = $request->meaningtmp_id;
        try {
            $meaningtmp = MeaningTemp::find($id);
            $meaningtmp->new_meaning = $request->meaning;
            $meaningtmp->language = $request->new_language;
            $meaningtmp->type = $request->new_type;
            $meaningtmp->status = IN_QUEUE;
            $meaningtmp->comment = $request->new_comment;

            DB::beginTransaction();
            $meaningtmp->save();
            DB::commit();
            $notification = array(
                'message' => 'You have been edited a declined contribtute successfully',
                'alert-type' => 'success'
            );
        } catch (\Exception $e) {
            DB::rollback();
            $notification = array(
                'message' => 'Something went wrong',
                'alert-type' => 'error'
            );
        }
        return redirect('user/history')->with($notification);
    }

    public function improveMeaning(ImproveMeaningRequest $request) {
        // dd($request->all());
        try {
            $oldMeaning = Meaning::find($request->old_meaning_id);
            $newMeaning = array(
                'opCode' => EDIT,
                'keyword_id' => $oldMeaning->keyword_id,
                'user_id' => Sentinel::getUser()->id,
                'old_meaning_id' => $oldMeaning->id,
                'new_meaning' => $request->new_meaning,
                'language' => $oldMeaning->language,
                'type' => $oldMeaning->type,
                'index' => $oldMeaning->index,
                'comment' => $request->comment,
                'status' => IN_QUEUE,
            );
            DB::beginTransaction();
            MeaningTemp::create($newMeaning);
            DB::commit();
            $notification = array(
                'message' => 'Request has been sent.',
                'alert-type' => 'success'
            );
        } catch (\Exception $e) {
            DB::rollback();
            $notification = array(
                'message' => 'Some thing wrong.',
                'alert-type' => 'error',
            );
        }
        return response()->json($notification);
    }

}
