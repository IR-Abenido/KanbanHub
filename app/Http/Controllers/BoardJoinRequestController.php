<?php

namespace App\Http\Controllers;

use App\Http\Requests\Board\AddJoinRequest;
use App\Http\Requests\Board\GetJoinRequests;
use App\Http\Requests\Board\JoinRequestResponse;
use App\Models\Board;
use App\Models\BoardJoinRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class BoardJoinRequestController extends Controller
{
    public function getRequests(GetJoinRequests $request)
    {
        $board = Board::findOrFail($request->boardId);

        $this->authorize('getJoinRequests', $board);

        $joinRequests = $board->accessRequests()->where('status', 'pending')->get();

        $mappedRequests = $joinRequests->map(fn($accessRequest) => [
            'id' => $accessRequest->id,
            'user_id' => $accessRequest->user->id,
            'name' => $accessRequest->user->name,
            'email' => $accessRequest->user->email,
            'requested_at' => Carbon::parse($accessRequest->created_at)->format('Y-m-d'),
        ]);

        return response()->json([
            'joinRequests' => $mappedRequests
        ]);
    }

    public function store(AddJoinRequest $request)
    {
        $userId = Auth::id();
        $board = Board::findOrFail($request->boardId);

        $this->authorize('storeJoinRequest', $board);

        $accessRequest = BoardJoinRequest::where('board_id', $request->boardId)
            ->where('user_id', $userId)->first();

        if (!$accessRequest) {
            BoardJoinRequest::create([
                'board_id' => $request->boardId,
                'user_id' => $userId,
                'status' => 'pending'
            ]);
        } else if ($accessRequest && $accessRequest->status === 'approved') {
            $accessRequest->status = 'pending';
            $accessRequest->save();
        } else {
            if ($accessRequest->status === "rejected") {
                $accessRequest->status = "pending";
                $accessRequest->save();
                return response()->json(['message' => 'success']);
            }
            return response()->json(['error' => 'A join request is already pending.'], 409);
        }

        return response()->noContent();
    }

    public function requestResponse(JoinRequestResponse $request)
    {
        $accessRequest = BoardJoinRequest::findOrFail($request->id);

        $board = Board::findOrFail($accessRequest->board_id);

        $this->authorize('joinRequestResponse', $board);

        if ($request->accept && $accessRequest->status === 'approved') {
            $accessRequest->status = 'pending';
            $accessRequest->save();
        } else if ($request->accept && $accessRequest->status === 'pending') {
            $accessRequest->status = 'approved';
            $accessRequest->save();
            $board->users()->syncWithoutDetaching([
                $request->userId => ['role' => $request->role]
            ]);
        } else if ($request->accept && $accessRequest->status === 'rejected') {
            $accessRequest->status = 'approved';
            $accessRequest->save();
            $board->users()->syncWithoutDetaching([
                $request->userId => ['role' => $request->role]
            ]);
        } else {
            $accessRequest->status = 'rejected';
            $accessRequest->save();
        }

        return response()->noContent();
    }
}
