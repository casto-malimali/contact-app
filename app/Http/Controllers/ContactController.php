<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ContactController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {

        $contact = Contact::where('user_id', $request->user()->id)->latest()->get();
        return $contact;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'id' => ['nullable', 'uuid'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'max:255'],
            'version' => ['nullable', 'integer'],
        ]);

        $contact = new Contact($data);
        $contact->id = $data['id'] ?? (string) Str::uuid();
        $contact->user_id = $request->user()->id;
        $contact->version = ($data['version'] ?? 0) + 1;
        $contact->save();

        return response()->json($contact, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Contact $contact)
    {
        abort_unless($contact->user_id === $request->user()->id, 403);
        return $contact;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $req, Contact $contact)
    {
        abort_unless($contact->user_id === $req->user()->id, 403);

        $data = $req->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'max:255'],
            'version' => ['nullable', 'integer'],
        ]);

        // simple conflict guard: client version must be >= server version
        if (($data['version'] ?? 0) < $contact->version) {
            return response()->json(['conflict' => $contact], 409);
        }

        $contact->fill($data);
        $contact->version = $contact->version + 1;
        $contact->save();

        return $contact;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $req, Contact $contact)
    {
        abort_unless($contact->user_id === $req->user()->id, 403);
        $contact->delete();
        return response()->noContent();
    }
}
