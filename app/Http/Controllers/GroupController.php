<?php
namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GroupController extends Controller
{
// Fetch all groups
    public function index()
    {
        $groups = Group::all();
        return response()->json($groups);
    }

// Create a new group
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'tag' => 'nullable|string|max:255',
        ]);

        $group = Group::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'tag' => $request->tag,
        ]);

        return response()->json($group, 201);
    }

// Show a single group
    public function show($id)
    {
        $group = Group::with('users')->find($id); // Load the group along with its users

        if (!$group) {
            return response()->json(['error' => 'Group not found'], 404);
        }

        return response()->json($group);
    }


    public function update(Request $request, $id)
    {
        // Validate the request
        $request->validate([
            'name' => 'required|string|max:255',
            'tag' => 'nullable|string|max:255',
        ]);

        // Fetch the group
        $group = Group::find($id);

        if (!$group) {
            return response()->json(['error' => 'Group not found'], 404);
        }

        // Update the group
        $group->update([
            'name' => $request->name,
            'slug' => \Illuminate\Support\Str::slug($request->name),
            'tag' => $request->tag,
        ]);

        return response()->json($group);
    }


        // Delete a group
    public function destroy($id)
    {
        // Find the group by ID
        $group = Group::find($id);

        // Check if the group exists
        if (!$group) {
            return response()->json(['error' => 'Group not found'], 404);
        }

        try {
            // Delete the group
            $group->delete();

            return response()->json(['message' => 'Group deleted successfully'], 200);
        } catch (\Exception $e) {
            // Handle any errors that occur during deletion
            return response()->json([
                'error' => 'Failed to delete group',
                'details' => $e->getMessage(),
            ], 500);
        }
    }


// Add users to a group
    public function addUsers(Request $request, $id)
    {
        $group = Group::find($id);
        try {
            // Validate the request
            $request->validate([
                'user_ids' => 'required|array',
                'user_ids.*' => 'exists:users,id',
            ]);
        }
        catch (\Exception $e) {
            // Handle any exceptions
            return response()->json([
                'error' => 'Failed to add users to the group',
                'details' => $e->getMessage(),
            ], 500);
        }
        try {
            // Attach users to the group
            $group->users()->syncWithoutDetaching($request->user_ids);

            return response()->json([
                'message' => 'Users added to the group successfully',
                'group' => $group->load('users'), // Load the updated users relationship
            ], 200);
        } catch (\Exception $e) {
            // Handle any exceptions
            return response()->json([
                'error' => 'Failed to add users to the group',
                'details' => $e->getMessage(),
            ], 500);
        }
    }


// Remove users from a group
    public function removeUsers(Request $request, Group $group)
    {
        $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        $group->users()->detach($request->user_ids);

        return response()->json(['message' => 'Users removed from group']);
    }

// List users in a group
    public function listUsers(Group $group)
    {
        $users = $group->users;
        return response()->json($users);
    }
}
