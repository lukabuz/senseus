<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Mail;

use Illuminate\Http\Request;
use App\Signature;
use App\Mail\VerificationMail;
use Socialite;

class MainController extends Controller
{
    //
    public $georgianMessages = [
        'firstName.required' => 'გთხოვთ შეიყვანოთ სახელი.',
        'firstName.min' => 'სახელი უნდა იყოს მინიმუმ 1 ასო.',
        'firstName.max' => 'სახელი უნდა იყოს მაქსიმუმ 50 ასო.',
        'lastName.required' => 'გთხოვთ შეიყვანოთ გვარი.',
        'lastName.min' => 'გვარი უნდა იყოს მინიმუმ 1 ასო.',
        'lastName.max' => 'გვარი უნდა იყოს მაქსიმუმ 50 ასო.',
        'email.required' => 'გთხოვთ შეიყვანოთ მეილი.',
        'email.email' => 'მეილის ფორმატი არასწორია.'
    ];

    public $englishMessages = [
        'firstName.required' => 'Please enter your name.',
        'firstName.min' => 'Name must be at least 1 character.',
        'firstName.max' => 'Name must be a maximum of 50 characters.',
        'lastName.required' => 'Please enter your last name.',
        'lastName.min' => 'Last name must be at least 1 character.',
        'lastName.max' => 'Last name must be a maximum of 50 characters.',
        'email.required' => 'Please enter your email.',
        'email.email' => 'Please enter a valid email.'
    ];

    public function getStatus(){
        return response()->json([
            'status' => 'success',
            'data' => [
                'signatureCount' => Signature::where('verificationToken', null)->count(),
                'signatures' => Signature::where('verificationToken', null)->where('showInfo', true)
            ]
        ]);
    }

    public function signWithEmail(Request $request){
        if($request->input('language') == 'en'){
            $messages = $this->englishMessages;
            $mailErrorMessage = 'An user has already signed this petition with the provided email.';
        } else {
            $messages = $this->georgianMessages;
            $mailErrorMessage = 'ამ მეილით ხელმოწერა უკვე დაფიქსირებულია.';
        }

        $validatedData = $request->validate([
            'firstName' => 'required|min:1|max:50',
            'lastName' => 'required|min:1|max:50',
            'email' => 'required|email'
        ], 
        $messages
        );

        if(Signature::where('email', $request->input('email'))->count() > 0) { 
            return response()->json([
                'status' => 'error',
                'error' => $mailErrorMessage
            ]);
        }

        $signature = new Signature;

        if($request->input('showInfo') == 'true'){
            $signature->showInfo = true;
        } else {
            $signature->showInfo = false;
        }

        $signature->firstName = $request->input('firstName');
        $signature->lastName = $request->input('lastName');
        $signature->email = '*********';
        $signature->verificationMethod = 'email';

        $signature->verificationToken = str_random(25);

        $signature->save();

        Mail::to($request->input('email'))->send(new VerificationMail($signature->verificationToken));

        return response()->json([
            'status' => 'success'
        ]);
    }

    public function verify($verificationToken){
        $signature = Signature::where('verificationToken', $verificationToken)->firstOrFail();
        
        $signature->verificationToken = null;

        $signature->save();

        return response()->json([
            'status' => 'success'
        ]);
    }

    public function facebookRedirect(){
        return Socialite::driver('facebook')->scopes(['public_profile','email'])->stateless()->redirect();
    }

    public function facebookCallback(Request $request){
        $user = Socialite::driver('facebook')->stateless()->user();

        $name = $user->getName();
        $email = $user->getEmail();

        if(Signature::where('email', $email)->count() > 0) { 
            return response()->json([
                'status' => 'error',
                'error' => 'ამ მეილით ხელმოწერა უკვე დაფიქსირებულია. An user has already signed this petition with the provided email.'
            ]);
        }

        $signature = new Signature;

        $signature->showInfo = true;
        $signature->firstName = $name;
        $signature->email = $email;
        $signature->verificationMethod = 'email';

        $signature->verificationToken = null;

        $signature->save();

        return response()->json([
            'status' => 'success'
        ]);
    }
}
