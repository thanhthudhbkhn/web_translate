<?php
namespace App\Http\Controllers\Admin;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Meaning;
use App\Keyword;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    /*
    * @todo show all words to admin
    * @return Illuminate\resource\views\admin\keyword_list
    */
    public function wordList(){   	    	
        $meaning=Meaning::all();
        return view('admin.keyWordlist',['meaning'=>$meaning]);
    }
    
    public function getKeywordAdd(){
        return view('admin.keywordAdd');
    }
    
    public function postKeywordAdd(Request $request){
        DB::beginTransaction();
        try {
            //create new keyword
            $keyword = new keyword();
            $keyword->value = $request->txtKeyWord;
            $keyword->status= APPROVED;
            $keyword->save();
            $id=$keyword->keyword_id;



            //create new meaning
            foreach ( $request->translate as $key => $value ) {
                $meaning = new meaning();
                $meaning->keyword_id = $id;
                $meaning->value = $value['meaning'];
                $meaning->index = $key;
                $meaning->status = APPROVED;
                $meaning->language = $value['language'];
                $meaning->save();
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return 'Error: ' . $e->getMessage();
        }
        return redirect('admin/keywordList')->with('notification','You have successfully add new keyword');
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

    public function checkExistKeyword(Request $request)
    {
        $count = keyword::where('value', $request->keyword)->count();
        return $count;
    }
}
