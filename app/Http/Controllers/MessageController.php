<?php

namespace App\Http\Controllers;

use App\Events\MessageSend;
use App\Message;
use Illuminate\Http\Request;
use App\User;
class MessageController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
    public function user_list(){
        
        $users = User::latest()->where('id','!=',auth()->user()->id)->get();
        return response()->json($users,200);
    }

    public function user_message($user_id){

        if(!\Request::ajax()){
            return abort(404);
        }

        $user = User::find($user_id);
        $messages = $this->message_by_user_id($user_id);

        return response()->json([
            'user' => $user,
            'messages' => $messages
        ]);

    }

    public function send_message(Request $request){

        if(!\Request::ajax()){
            return abort(404);
        }

        $message = Message::create([
            'message' => $request->message,
            'from' => auth()->user()->id,
            'to' => $request->user_id,
            'type' => 0
        ]);

        $message = Message::create([
            'message' => $request->message,
            'from' => auth()->user()->id,
            'to' => $request->user_id,
            'type' => 1
        ]);

        broadcast(new MessageSend($message));
    }

    public function delete_single_message($id){

        if(!\Request::ajax()){
            return abort(404);
        }
        
        $message = Message::find($id);
        $message->delete();
        return response()->json($message);

    }

    public function delete_all_message($user_id){

        $messages = $this->message_by_user_id($user_id);
        foreach($messages as $value){
            Message::find($value->id)->delete();
        }

    }

    public function message_by_user_id($user_id){
        $messages = Message::with('user')->where(function($q) use ($user_id){
            $q->where('from',auth()->user()->id);
            $q->where('to',$user_id);
            $q->where('type',0);
        })->orWhere(function($q) use ($user_id){
            $q->where('from',$user_id);
            $q->where('to',auth()->user()->id);
            $q->where('type',1);
        })->get();
        return $messages;
    }
}
