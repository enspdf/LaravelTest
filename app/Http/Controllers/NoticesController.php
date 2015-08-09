<?php namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Http\Requests\PrepareNoticeRequest;
use App\Notice;
use App\Provider;
use Auth;
use Illuminate\Auth\Guard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class NoticesController extends Controller {
    public function __construct()
    {
        $this->middleware('auth');
        parent::__construct();
    }
    public function index()
    {
        $notices = $this->user->notices; //()->where('content_removed', 0)->get(); don't see the results that now has been removed
        return view('notices.index', compact('notices'));
    }
    public function create()
    {
        //get list of providers
        $providers = Provider::lists('name','id');
        return view('notices.create', compact('providers'));
    }
    public function confirm(PrepareNoticeRequest $request)
    {
        $datos = $request->all();
        $data = $request->all() + [
                'name' => $this->user->name,
                'email' => $this->user->email,
        ];
        $template = view()->file(app_path('Http/Templates/dmca.blade.php'), $data);
        session()->flash('dmca', $datos);
        return view('notices.confirm', compact('template'));
    }
    public function store(Request $request)
    {
        $notice = $this->createNotice($request);
        Mail::queue(['text' => 'emails.dmca'], compact('notice'), function($message) use ($notice) {
            $message->from($notice->getOwnerEmail())
                    ->to($notice->getRecipientEmail())
                    ->subject('DMCA Notice');
        });
        flash('Your DMCA notice has been delivered!');
        return redirect('notices');
    }
    public function update($noticeId, Request $request)
    {
        $isRemoved = $request->has('content_removed');
        Notice::findOrFail($noticeId)
               ->update(['content_removed' => $isRemoved]);
        //return redirect()->back();
    }
    public function createNotice(Request $request)
    {
        $notice = session()->get('dmca') + ['template' => $request->input('template')];
        $notice = $this->user->notices()->create($notice);
        return $notice;
    }
}
