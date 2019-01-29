<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Signature;
use App\Mail\VerificationMail;
use Socialite;

class MainController extends Controller
{
    //
    public $georgianMessages = [
        'firstName.required' => 1,
        'firstName.min' => 2,
        'firstName.max' => 3,
        'lastName.required' => 4,
        'lastName.min' => 5,
        'lastName.max' => 6,
        'email.required' => 7,
        'email.email' => 8
    ];

    public function getStatus(){
        return response()->json([
            'status' => 'success',
            'data' => [
                'signatureCount' => Signature::where('verificationToken', null)->count(),
                'signatures' => Signature::where('verificationToken', null)->where('showInfo', true)->orderBy('id', 'desc')->get()
            ]
        ]);
    }

    public function signWithEmail(Request $request){
        $validatedData = $request->validate([
            'firstName' => 'required|min:1|max:50',
            'lastName' => 'required|min:1|max:50',
            'email' => 'required|email'
        ], 
        $this->georgianMessages
        );

        if(Signature::where('email', $request->input('email'))->count() > 0) { 
            return response()->json([
                'status' => 'error',
                'errors' => [
                    'email' => [9]
                ]
            ], 422);
        }

        $signature = new Signature;

        $signature->showInfo = true;

        $signature->firstName = $request->input('firstName');
        $signature->lastName = $request->input('lastName');
        $signature->email = $request->input('email');
        $signature->verificationMethod = 'email';

        $signature->verificationToken = str_random(25);

        $signature->save();

        Mail::to($request->input('email'))->send(new VerificationMail($signature->verificationToken));

        $count = Signature::where('verificationToken', null)->count();
        Log::info('Email verification process started.',  ['Name' => $signature->firstName . ' ' . $signature->lastName, 'Signature count' => $count]);

        return response()->json([
            'status' => 'success'
        ]);
    }

    public function verify($verificationToken){
        $signature = Signature::where('verificationToken', $verificationToken)->firstOrFail();
        
        $signature->verificationToken = null;

        $signature->save();

        $count = Signature::where('verificationToken', null)->count();
        Log::info('Email verified.', ['Name' => $signature->firstName . ' ' . $signature->lastName, 'Signature count' => $count]);

        return redirect()->away('https://senseus.ge/?verified=true');
    }

    public function facebookRedirect(){
        return Socialite::driver('facebook')->scopes(['public_profile','email'])->stateless()->redirect();
    }

    public function facebookCallback(Request $request){
        $user = Socialite::driver('facebook')->stateless()->user();

        $name = $user->getName();
        $email = $user->getEmail();

        if(Signature::where('email', $email)->count() > 0) { 
            return redirect()->away('https://senseus.ge/?errorCode=9');
        }

        $signature = new Signature;

        $signature->showInfo = true;
        $signature->firstName = $name;
        $signature->email = $email;
        $signature->verificationMethod = 'facebook';

        $signature->verificationToken = null;

        $signature->save();

        $count = Signature::where('verificationToken', null)->count();
        Log::info('Facebook signature recieved.', ['Name' => $signature->firstName, 'Signature count' => $count]);

        return redirect()->away('https://senseus.ge/?verified=true');
    }
}
