<?php

namespace App\Http\Controllers;

use App\Models\Dnas;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Registro de usuario
     */
    public function signUp(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|string|email|unique:users',
            'password' => 'required|string'
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password)
        ]);

        return response()->json([
            'message' => 'Successfully created user!'
        ], 201);
    }

    /**
     * Inicio de sesión y creación de token
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
            'remember_me' => 'boolean'
        ]);

        $credentials = request(['email', 'password']);

        if (!Auth::attempt($credentials))
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);

        $user = $request->user();
        $tokenResult = $user->createToken('Personal Access Token');

        $token = $tokenResult->token;
        if ($request->remember_me)
            $token->expires_at = Carbon::now()->addWeeks(1);
        $token->save();

        return response()->json([
            'access_token' => $tokenResult->accessToken,
            'token_type' => 'Bearer',
            'expires_at' => Carbon::parse($token->expires_at)->toDateTimeString()
        ]);
    }

    /**
     * Cierre de sesión (anular el token)
     */
    public function logout(Request $request)
    {
        $request->user()->token()->revoke();

        return response()->json([
            'message' => 'Successfully logged out'
        ]);
    }

    /**
     * Obtener el objeto User como json
     */
    public function user(Request $request)
    {
        return response()->json(Auth::guard('api')->user());
    }

    public function forceUsers(Request $request)
    {
        $request->validate([
            "dna"    => "required|array",
            "dna.*"  => "required|string",
        ]);
        $dna = request(['dna']);

        $reg = Dnas::where('dna', serialize($dna))->first();
        if($reg){
            if ( $reg->result == 0 ) {
                return response()->json(['ERROR' => 'Non Force-User'],403);
            }else {
                return response()->json(['SUCCESS' => 'Force-User']);
            }
        }

        $reg = new Dnas();
        $reg->dna = serialize($dna);
        if ( $this->isForceUser($dna) == 0 ) {
            $reg->result = 0;
            $reg->save();
            return response()->json(['ERROR' => 'Non Force-User'],403);
        }else {
            $reg->result = 1;
            $reg->save();
            return response()->json(['SUCCESS' => 'Force-User']);
        }
    }

    protected function isForceUser($dna)
    {
        $string_valid = [
            'AAAA',
            'TTTT',
            'CCCC',
            'GGGG',
        ];
        return $this->horizontal($dna, $string_valid) + $this->vertical($dna, $string_valid) + $this->diagonal($dna, $string_valid);
    }

    protected function horizontal($dna, $string_valid)
    {
        foreach($dna as $item) {
            return $this->verify_dna($item, $string_valid);
        }
        return false;
    }

    protected function vertical($dna, $string_valid)
    {
        $output = array();
        $str_aux = "";
        foreach($dna as $item){
            foreach($item as $str){
                $key = 0;
                $array_item = str_split($str);
                foreach($array_item as $value){
                    if(!isset($output[$key])){
                        $output[$key] = $value;
                    }else{
                        $output[$key] .= $value;
                    }
                    $key++;
                }
            }
        }
        return $this->verify_dna($output, $string_valid);
    }

    protected function diagonal($dna, $string_valid)
    {
        $output = "";
        foreach($dna as $item){
            $key = 0;
            foreach($item as $str){
                $array_item = str_split($str);
                $output .= $array_item[$key];
                $key++;
            }
        }
        return $this->verify_dna([$output], $string_valid);
    }

    protected function verify_dna($item,$string_valid){
        foreach($item as $str_item){
            foreach($string_valid as $str) {
                if (strpos($str_item, $str) !== false) {
                    return 1;
                }
            }
        }
        return 0;
    }

}
