<?php

namespace App\Http\Controllers\Legacy;

use App\Video;
use App\Prompt;
use App\Stat;
use App\User;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\ApiController;
use Illuminate\Contracts\Filesystem\Filesystem;
use JWTAuth;
use App\Exceptions\NotAuthorizedException;

class VideoController extends ApiController
{
    /**
     * Create a new authentication controller instance.
     *
     * @return void
    */
    public function __construct()
    {
        $this->middleware('jwt.auth', ['except' => ['getSharedVideos', 'view', 'show']]);
        $this->middleware('guest', ['only' => 'getSharedVideos', 'view', 'show']);
    }

    /**
     * save video
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $user = \Auth::user();
        $this->validate($request, [
            'prompt_id' => 'exists:prompts,id'
        ]);

        //checking if video exists and prompt is legit
        $prompt = Prompt::where('id', '=', $request->prompt_id)
            ->where(function($query) use ($user){
                $query->whereNull('user_id')
                    ->orWhere('user_id', '=', $user->id);
            })
            ->with(['videos' => function ($query) use ($user){
                $query->where('user_id', '=', $user->id);
            }])
            ->first();

        if (count($prompt->videos)) {
            return response()->json(['error' => 'video_already_exists'], 400);
        }

        //saving video info to db
        $video = new Video;
        if ($request->video_url) { //no upload
            $this->validate($request, [
                'duration' => 'numeric'
            ]);
            $video->video_url = $request->video_url;
            $video->thumb_url = ''; // not used for now
            $video->duration = round($request->duration);
        }else{
            //uploading to s3
            $s3 = \Storage::disk('s3');
            $videoFile = $request->file('video');
            $rnd = str_random(30);
            $fileName =  $rnd . '.' . $videoFile->getClientOriginalExtension();
            $videoPath = 'videos/' . $user->id . '/' . $fileName;
            $s3->put($videoPath, file_get_contents($videoFile));

            //getting duration
            $ffprobe = \FFMpeg\FFProbe::create();
            $duration = $ffprobe ->streams($videoFile) // extracts streams informations
                ->videos()                      // filters video streams
                ->first()                       // returns the first video stream
                ->get('duration');

            //saving tmp thumbs and uploads to s3
            $ffmpeg = \FFMpeg\FFMpeg::create();
            $video = $ffmpeg->open($videoFile);
            $video->frame(\FFMpeg\Coordinate\TimeCode::fromSeconds(1))
                ->save($rnd . '.jpg');
            $thumbPath = 'thumbs/' . $user->id . '/' . $rnd . '.jpg';
            $s3->put($thumbPath, file_get_contents($rnd. '.jpg'));
            unlink($rnd.'.jpg');

            $video->video_url = $videoPath;
            $video->thumb_url = $thumbPath;
            $video->duration = round($duration);
        }

        $video->user_id = $user->id;
        $video->prompt_id = $request->prompt_id;
        $video->shared = 0;
        $video->save();
        return response()->json(compact('video'));
    }

    /**
     * get video information
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        $video = Video::with('user')->findOrFail($id);
        try {
            $user = JWTAuth::parseToken()->toUser();
        }catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            $user = null;
        }

        if (!$user && !$video->shared){
            throw new NotAuthorizedException;
        }else if ($user && !$user->isAdmin() && $video->user_id != $user->id) {
            throw new NotAuthorizedException;
        }

         //disabling access to deactivated user's video
        if ($video->user->status == 0) {
            throw new NotAuthorizedException;
        }

        //fetching presigned url
        //TODO: presigned url for thumbs
        $s3 = \Storage::disk('s3');
        $command = $s3->getDriver()->getAdapter()->getClient()->getCommand('GetObject', [
            'Bucket' => config('filesystems.disks.s3.bucket'),
            'Key'    => $video->video_url
        ]);
        $request = $s3->getDriver()->getAdapter()->getClient()->createPresignedRequest($command, config('legacysuite.url_ttl'));
        $video->video_url = (string) $request->getUri();
        return response()->json(compact('video'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $user = \Auth::user();
        $video = Video::findOrFail($id);
        if ($user->isAdmin() || $video->user_id === $user->id){
            //$s3 = \Storage::disk('s3');
            //$s3->delete($video->thumb_url);
            //$s3->delete($video->video_url);
            $video->delete();
            return response()->json(['success' => true]);
        }else{
            throw new NotAuthorizedException;
        }
    }

    /**
     * return all shared videos
     *
     * @return \Illuminate\Http\Response
     */
    public function getSharedVideos(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $videos = Video::with('prompt')
            ->where('user_id', '=', $id)
            ->where('shared', '=', 1)
            ->get();

        $results = $videos->toArray();
        foreach ($videos as $key => $video){
            $results[$key]['prompt'] = $video->prompt->toArray();
        }
        return response()->json(['videos' => $results]);
    }

    /**
     * return all videos
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user = \Auth::user();
        $user_id = $user->id;
        if ($user->isAdmin() && $request->user_id) {
            $this->validate($request, [
                'user_id' => 'exists:users,id'
            ]);
            $user_id = $request->user_id;
        }

        $videos = Video::with('prompt')
            ->where('user_id', '=', $user_id)
            ->get();

        $results = $videos->toArray();
        foreach ($videos as $key => $video){
            $results[$key]['prompt'] = $video->prompt->toArray();
        }
        return response()->json(['videos' => $results]);
    }

    /**
     * Update Video
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id) {
        $user = \Auth::user();
        $video = Video::findOrFail($id);
        if ($user->isAdmin() || $video->user_id === $user->id){
            if (isset($request->note)){
                $video->note = $request->note;
            }
            if (isset($request->shared)) {
                $this->validate($request, [
                    'shared' => 'in:0,1'
                ]);
                $video->shared = $request->shared;
            }
            if (isset($request->video_url)) {
                $video->video_url = $request->video_url;
            }
            if (isset($request->duration)) {
                $this->validate($request, [
                    'duration' => 'numeric'
                ]);
                $video->duration = $request->duration;
            }
            $video->save();
            return response()->json(['success' => true]);
        }else{
            throw new NotAuthorizedException;
        }
    }

    /**
     * Populate one view history
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function view(Request $request, $id)
    {
        try {
            $user = JWTAuth::parseToken()->toUser();
        }catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            $user = null;
        }

        //TODO: implement duration
        if ($user) {
            $stat = new Stat;
            $stat->user_id = $user->id;
            $stat->video_id = $id;
            $stat->ip = $request->ip();
            $stat->save();
        }


        // For now using video->view for speed
        $video = Video::findOrFail($id);
        $video->views = $video->views + 1;
        $video->save();

        return response()->json(['success' => true]);
    }

}
