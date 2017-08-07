<?php
namespace App\Http\Controllers\Admin;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Meaning;
use App\Keyword;
use App\KeywordTemp;
use App\MeaningTemp;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    /*
    * @todo show all words to admin
    * @return Illuminate\resource\views\admin\keyword_list
    */
    public function wordList(){   	    	
        $meaning = Meaning::all();
        return view('admin.keyWordList',['meaning'=>$meaning]);
    }
    
    public function addKeyword(){
        return view('admin.keywordAdd');
    }
    
    public function processAddKeyword(Request $request){
        try {
            DB::beginTransaction();
            // add new keyword
            $keyword = new Keyword;
            $keyword->keyword = $request->keyword;
            $keyword->status= APPROVED;
            $keyword->save();          
            // add new meanings
            foreach ( $request->translate as $key => $value ) {
                $meaning = new Meaning;
                $meaning->keyword_id = $keyword->id;
                $meaning->meaning = $value['meaning'];
                $meaning->index = $key;
                $meaning->status = APPROVED;
                $meaning->language = $value['language'];
                $meaning->save();
            }
            $notification = 'You have successfully add new keyword.';
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            $notification = 'Something went wrong!';
        }
        return redirect('admin/keywordList')->with('notification', $notification);
    }
    
    /*
    *@todo allow admin to solf delete word 
    */
    public function deleteWord($id){
    	try {
            DB::beginTransaction();
            $meaning= meaning::where($id)->first();
            $meaning->status= DELETED;
            $meaning->save();
            DB::commit();
    	} catch (\Exception $e) {
    		DB::rollback();
    	}
    	$meaning= meaning::all();
    	return view('admin.keyWordlist',['meaning'=>$meaning]);
    }

    /**
     * return List request of keyword table
     * @return [type] [description]
     */
    public function keywordTempList()
    {
        $data = KeywordTemp::where('status', IN_QUEUE)->get();
        return view('admin.approve.keyword.list', ['data' => $data]);
    }

    /**
     * Approve request on keyword table
     * @param  [type] $id     [description]
     * @param  [type] $opCode [description]
     * @return [type]         [description]
     */
    public function approveChangesOnKeywordTable(Request $request)
    {
            $notification = array(
                    'message' => 'Request is not exist.', 
                    'alert-type' => 'error'
                );
        $keywordTemp = KeywordTemp::find($request->id);
        $opCode = $request->opCode;
        if (!$request->has('id') || !$request->has('opCode') || ($request->opCode != ADD && $request->opCode != EDIT)) {
            $notification = array(
                    'message' => 'Invalid request.', 
                    'alert-type' => 'error'
                );
        }
        if (isset($opCode) && $opCode == ADD && $keywordTemp != null) {
            $notification = AdminController::approveAddKeyword($keywordTemp);
        }
        if ($opCode == EDIT && $keywordTemp != null){
            $notification = AdminController::approveEditKeyword($keywordTemp);
        }
        return redirect()->route('keywordTempList')->with($notification);
    }

    /**
     * Approve adding new keyword 
     * @param  [type] $keywordTemp [description]
     * @return [type]              [description]
     */
    public function approveAddKeyword($keywordTemp)
    {
        try {
            DB::beginTransaction();
            $keyword = Keyword::find($keywordTemp->old_keyword_id);
            $keyword->status = APPROVED;
            $keyword->save();
            $keywordTemp->status = APPROVED;
            $keywordTemp->save();
            DB::commit();
            $notification = array(
                    'message' => 'Keyword \''. $keyword['keyword'] .'\' added successfully.', 
                    'alert-type' => 'success'
                );
        } catch (\Exception $e) {
            DB::rollback();
            $notification = array(
                    'message' => 'Something went wrong.', 
                    'alert-type' => 'error'
                );
        }
        return $notification;
    }

    /**
     * Approve editing keyword
     * @param  [type] $keywordTemp [description]
     * @return [type]              [description]
     */
    public function approveEditKeyword($keywordTemp)
    {
        try {
            DB::beginTransaction();
            $keyword = Keyword::find($keywordTemp['old_keyword_id']);
            $notification = array(
                    'message' => 'Keyword edited successfully. <br>\''.$keyword->keyword.'\' to \''.$keywordTemp['new_keyword'].'\'', 
                    'alert-type' => 'success'
                );
            $keyword->keyword = $keywordTemp['new_keyword'];
            $keyword->save();
            $keywordTemp->status = APPROVED;
            $keywordTemp->save();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            $notification = array(
                    'message' => 'Something went wrong.', 
                    'alert-type' => 'error'
                );
        }
        return $notification;
    }
    /**
     * Decline request on keyword table
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function declineChangesOnKeywordTable(Request $request)
    {   
        if ($request->has('id')) {
            $keywordTemp = KeywordTemp::find($request->id);
            try {
                DB::beginTransaction();
                $keywordTemp->status = DECLINED;
                $keywordTemp->comment = $request->get('cmt');
                $keywordTemp->save();
                DB::commit();
            $notification = array(
                    'message' => 'Request declined successfully.', 
                    'alert-type' => 'success'
                );
            } catch (\Exception $e) {
                DB::rollback();
            $notification = array(
                    'message' => 'Something went wrong.', 
                    'alert-type' => 'error'
                );
            }
        } else {
            $notification = array(
                    'message' => 'Request is not exist.', 
                    'alert-type' => 'error'
                );
        }
        return redirect()->route('keywordTempList')->with($notification);
    }

    /**
     * Delete a request
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function deleteRequest(Request $request)
    {
        try {
            DB::beginTransaction();
            $keywordTemp = KeywordTemp::find($request->id);
            $keywordTemp->status = DELETED;
            $keywordTemp->save();
            DB::commit();
            $notification = array(
                    'message' => 'Request deleted.', 
                    'alert-type' => 'success'
                );
        } catch (\Exception $e) {
            DB::rollback();
            $notification = array(
                    'message' => 'Something went wrong.', 
                    'alert-type' => 'error'
                );
        }
        return redirect()->route('keywordTempList')->with($notification);
    }
    

    /**
     * return list request on meaning table
     * @return [type] [description]
     */
    public function meaningTempList()
    {
        $data = MeaningTemp::where('status', IN_QUEUE)->get();
        return view('admin.approve.meaning.list', ['data' => $data]);
    }

    /**
     * Approve changes on meaning table
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function approveChangesOnMeaningTable(Request $request)
    {
            $notification = array(
                    'message' => 'Request is not exist.', 
                    'alert-type' => 'error'
                );
        $opCode = $request->opCode;
        $meaningTemp = MeaningTemp::find($request->id);
        if (!$request->has('opCode') || !$request->has('id') || ($opCode != ADD && $opCode != EDIT)) {
            $notification = array(
                    'message' => 'Invalid request.', 
                    'alert-type' => 'error'
                );
        }
        if (isset($opCode) && $opCode == ADD && $meaningTemp != null) {
            $notification = AdminController::approveAddMeaning($meaningTemp);
        }
        if ($opCode == EDIT && $meaningTemp != null){
            $notification = AdminController::approveEditMeaning($meaningTemp);
        }
        return redirect()->route('meaningTempList')->with($notification);
    }

    public function approveAddMeaning($meaningTemp)
    {
        try {
            DB::beginTransaction();
            $meaning = new Meaning;
            $meaning->meaning = $meaningTemp['new_meaning'];
            $meaning->language = $meaningTemp['language'];
            $meaning->index = $meaningTemp['index'];
            $meaning->keyword_id = $meaningTemp['keyword_id'];
            $meaning->status = APPROVED;
            $meaning->save();
            $meaningTemp->status = APPROVED;
            $meaningTemp->save();
            DB::commit();
            $notification = array(
                    'message' => "Successfully added new meaning.<br>".$meaning->keyword['keyword'].' : '.$meaning->meaning, 
                    'alert-type' => 'success'
                );
        } catch (\Exception $e) {
            DB::rollback();
            $notification = array(
                    'message' => 'Something went wrong.', 
                    'alert-type' => 'error'
                );
        }
        return $notification;
    }

    public function approveEditMeaning($meaningTemp)
    {
        try {
            DB::beginTransaction();
            $meaning = Meaning::find($meaningTemp['old_meaning_id']);
            $meaning->meaning = $meaningTemp['new_meaning'];
            $meaning->save();
            $meaningTemp->status = APPROVED;
            $meaningTemp->save();
            DB::commit();
            $notification = array(
                    'message' => 'Successfully edited meaning.', 
                    'alert-type' => 'success'
                );
        } catch (\Exception $e) {
            DB::rollback();
            $notification = array(
                    'message' => 'Something went wrong.', 
                    'alert-type' => 'error'
                );
        }
        return $notification;
    }

    /**
     * Decline changes on meaning table
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function declineChangesOnMeaningTable(Request $request)
    {
        if (!$request->has('id')) {
            $notification = array(
                    'message' => 'Invalid request.', 
                    'alert-type' => 'error'
                );
        }
        $meaningTemp = MeaningTemp::find($request->id);
        if($meaningTemp != null){
            try {
                DB::beginTransaction();
                $meaningTemp->status = DECLINED;
                $meaningTemp->comment = $request->get('cmt');
                $meaningTemp->save();
                DB::commit();
                $notification = array(
                        'message' => 'Request declined successfully.', 
                        'alert-type' => 'success'
                    );
            } catch (\Exception $e) {
                DB::rollback();
            $notification = array(
                    'message' => 'Something went wrong.', 
                    'alert-type' => 'error'
                );
            }
        }else{
            $notification = array(
                    'message' => 'Request is not exist.', 
                    'alert-type' => 'error'
                );
        }
        return redirect()->route('meaningTempList')->with($notification);
    }

    public function deleteRequestOnMeaningTable(Request $request)
    {
        try {
            DB::beginTransaction();
            $meaningTemp = MeaningTemp::find($request->id);
            $meaningTemp->status = DELETED;
            $meaningTemp->save();
            DB::commit();
            $notification = array(
                    'message' => 'Request deleted successfully.', 
                    'alert-type' => 'success'
                );
        } catch (\Exception $e) {
            DB::rollback();
            $notification = array(
                    'message' => 'Something went wrong.', 
                    'alert-type' => 'error'
                );
        }
        return redirect()->route('meaningTempList')->with($notification);
    }

}