<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Str;
class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:manage users')->except(['profile', 'updateProfile', 'updatePassword']);
    }

    /**
     * Liste des utilisateurs
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = User::with('roles');

            // Filtres
            if ($request->filled('is_active')) {
                $query->where('is_active', $request->is_active);
            }

            if ($request->filled('role')) {
                $query->role($request->role);
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('login', 'LIKE', "%{$search}%")
                      ->orWhere('email', 'LIKE', "%{$search}%");
                });
            }

            // Exclure les utilisateurs supprimés
            $query->where('is_delete', false);

            // Tri
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            $users = $query->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $users
            ]);
        }

        $roles = Role::all();
        return view('Users.index', compact('roles'));
    }

    /**
     * Afficher un utilisateur
     */
    public function show($id, Request $request)
    {
        try {
            $user = User::with(['roles', 'permissions'])->findOrFail($id);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'data' => $user
                ]);
            }

            return view('users.show', compact('user'));

        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non trouvé.'
                ], 404);
            }
            abort(404);
        }
    }

    /**
     * Créer un utilisateur
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'login' => 'required|string|unique:users,login|max:255',
            'email' => 'nullable|email|unique:users,email|max:255',
            'password' => 'required|string|min:6|confirmed',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,name',
            'is_active' => 'boolean',
        ]);

        try {
            DB::beginTransaction();

            $userData = [
                'name' => $request->name,
                'login' => $request->login,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'is_active' => $request->get('is_active', true),
            ];

            // Gérer l'upload de la photo
            if ($request->hasFile('photo')) {
                $image = $request->file('photo');
                $filename = Str::uuid() . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('photos'), $filename);
                $userData['photo'] = 'photos/' . $filename;
            }


            $user = User::create($userData);

            // Assigner les rôles
            $user->assignRole($request->roles);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Utilisateur créé avec succès.',
                'data' => $user->load('roles')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mettre à jour un utilisateur
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'login' => ['required', 'string', 'max:255', Rule::unique('users')->ignore($user->id)],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:6|confirmed',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,name',
            'is_active' => 'boolean',
        ]);

        try {
            DB::beginTransaction();

            $userData = [
                'name' => $request->name,
                'login' => $request->login,
                'email' => $request->email,
                'is_active' => $request->get('is_active', $user->is_active),
            ];

            // Mettre à jour le mot de passe si fourni
            if ($request->filled('password')) {
                $userData['password'] = Hash::make($request->password);
            }

            // Gérer l'upload de la photo
            if ($request->hasFile('photo')) {
                // Supprimer l'ancienne photo si ce n'est pas la photo par défaut
                if ($user->photo !== 'assets/images/default.jpg' && file_exists(public_path($user->photo))) {
                    unlink(public_path($user->photo));
                }

                // Récupérer le nouveau fichier
                $image = $request->file('photo');
                $filename = Str::uuid() . '.' . $image->getClientOriginalExtension();

                // Enregistrer dans le dossier public/photos
                $image->move(public_path('photos'), $filename);

                // Mettre à jour le chemin dans les données utilisateur
                $userData['photo'] = 'photos/' . $filename;
            }


            $user->update($userData);

            // Synchroniser les rôles
            $user->syncRoles($request->roles);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Utilisateur mis à jour avec succès.',
                'data' => $user->fresh(['roles'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer un utilisateur (soft delete)
     */
    public function destroy($id)
    {
        try {
            $user = User::findOrFail($id);

            // Empêcher la suppression de son propre compte
            if ($user->id === auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous ne pouvez pas supprimer votre propre compte.'
                ], 422);
            }

            // Marquer comme supprimé
            $user->update([
                'is_delete' => true,
                'is_active' => false
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Utilisateur supprimé avec succès.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Activer/Désactiver un utilisateur
     */
    public function toggleStatus($id)
    {
        try {
            $user = User::findOrFail($id);

            if ($user->id === auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous ne pouvez pas modifier votre propre statut.'
                ], 422);
            }

            $user->is_active = !$user->is_active;
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Statut mis à jour avec succès.',
                'data' => $user
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du statut.'
            ], 500);
        }
    }

    /**
     * Profil de l'utilisateur connecté
     */
    public function profile()
    {
        $user = auth()->user()->load(['roles', 'permissions']);
        return view('Users.profile', compact('user'));
    }

    /**
     * Mettre à jour le profil
     */
    public function updateProfile(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        try {
            DB::beginTransaction();

            $userData = [
                'name' => $request->name,
                'email' => $request->email,
            ];

            if ($request->hasFile('photo')) {
                // Supprimer l'ancienne photo si ce n'est pas la photo par défaut
                if ($user->photo !== 'assets/images/default.jpg' && file_exists(public_path($user->photo))) {
                    unlink(public_path($user->photo));
                }

                // Récupérer le nouveau fichier
                $image = $request->file('photo');
                $filename = Str::uuid() . '.' . $image->getClientOriginalExtension();

                // Enregistrer dans le dossier public/photos
                $image->move(public_path('photos'), $filename);

                // Mettre à jour le chemin dans les données utilisateur
                $userData['photo'] = 'photos/' . $filename;
            }

            $user->update($userData);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Profil mis à jour avec succès.',
                'data' => $user
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Changer le mot de passe
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = auth()->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Le mot de passe actuel est incorrect.'
            ], 422);
        }

        try {
            $user->update([
                'password' => Hash::make($request->password)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Mot de passe modifié avec succès.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la modification du mot de passe.'
            ], 500);
        }
    }
}
