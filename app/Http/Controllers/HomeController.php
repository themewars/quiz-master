<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use App\Models\Plan;
use App\Models\Quiz;
use App\Models\Setting;
use App\Models\Testimonial;
use App\Models\Subscription;
use App\Enums\SubscriptionStatus;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    public function index()
    {
        if (isset(getSetting()->enable_landing_page) && getSetting()->enable_landing_page == 0) {
            if (Auth::check() && Auth::user()->hasRole('admin')) {
                return redirect()->route('filament.admin.pages.dashboard');
            }

            if (Auth::check() && Auth::user()->hasRole('user')) {
                return redirect()->route('filament.user.pages.dashboard');
            }

            return redirect()->route('filament.auth.auth.login');
        }
        $plans = Plan::where('status', 1)->orderBy('price', 'asc')->get();
        $testimonials = Testimonial::all();
        $quizzes = Quiz::with('category')->whereNotNull('category_id')
            ->where('status', 1)->where('is_show_home', 1)
            ->where(function ($query) {
                $query->whereNull('quiz_expiry_date')
                    ->orWhere('quiz_expiry_date', '>=', Carbon::now());
            })->orderBy('id', 'desc')->get();
        $faqs = Faq::where('status', 1)->get();

        return view('home.index', compact('plans', 'testimonials', 'quizzes', 'faqs'));
    }

    public function terms()
    {
        $seeting = Setting::first();

        $terms = $seeting->terms_and_condition;

        return view('home.terms', compact('terms'));
    }

    public function policy()
    {
        $seeting = Setting::first();

        $policy = $seeting->privacy_policy;

        return view('home.policy', compact('policy'));
    }

    public function cookie()
    {
        $seeting = Setting::first();

        $cookie = $seeting->cookie_policy;

        return view('home.cookie', compact('cookie'));
    }

    public function refund()
    {
        $seeting = Setting::first();

        $refund = $seeting->refund_policy ?? '';

        return view('home.refund', compact('refund'));
    }

    public function contact()
    {
        return view('home.contact');
    }

    public function about()
    {
        return view('home.about');
    }

    public function pricing()
    {
        try {
            // Try to get plans, but handle any database errors
            $plans = Plan::where('status', 1)->orderBy('price', 'asc')->get();
        } catch (\Exception $e) {
            // If there's a database error, create some default plans
            $plans = collect([
                (object) [
                    'id' => 1,
                    'name' => 'Free Plan',
                    'price' => 0,
                    'frequency' => 'month',
                    'description' => 'Perfect for getting started',
                    'badge_text' => null,
                    'exams_per_month' => 3,
                    'max_questions_per_exam' => 10,
                    'pdf_export_enabled' => false,
                    'word_export_enabled' => false,
                    'website_quiz_enabled' => false,
                    'pdf_to_exam_enabled' => false,
                    'answer_key_enabled' => false,
                    'priority_support_enabled' => false,
                    'white_label_enabled' => false,
                ],
                (object) [
                    'id' => 2,
                    'name' => 'Basic Plan',
                    'price' => 999,
                    'frequency' => 'month',
                    'description' => 'Great for small teams',
                    'badge_text' => 'Popular',
                    'exams_per_month' => 20,
                    'max_questions_per_exam' => 25,
                    'pdf_export_enabled' => true,
                    'word_export_enabled' => true,
                    'website_quiz_enabled' => true,
                    'pdf_to_exam_enabled' => false,
                    'answer_key_enabled' => true,
                    'priority_support_enabled' => false,
                    'white_label_enabled' => false,
                ],
                (object) [
                    'id' => 3,
                    'name' => 'Pro Plan',
                    'price' => 1999,
                    'frequency' => 'month',
                    'description' => 'Perfect for growing businesses',
                    'badge_text' => null,
                    'exams_per_month' => 100,
                    'max_questions_per_exam' => 50,
                    'pdf_export_enabled' => true,
                    'word_export_enabled' => true,
                    'website_quiz_enabled' => true,
                    'pdf_to_exam_enabled' => true,
                    'answer_key_enabled' => true,
                    'priority_support_enabled' => true,
                    'white_label_enabled' => false,
                ]
            ]);
        }
        
        return view('home.pricing-simple', compact('plans'));
    }

    // public function index()
    // {
    //     /** @var User $user */
    //     $user = auth()->user();

    //     if ($user) {
    //         $role = $user->roles()->first();

    //         if ($role && $role->name === User::ADMIN_ROLE) {
    //             return redirect()->route('filament.admin.pages.dashboard');
    //         }

    //         if ($role && $role->name === User::USER_ROLE) {
    //             return redirect()->route('filament.user.pages.dashboard');
    //         }
    //     }

    //     return redirect()->route('filament.auth.auth.login');
    // }
}
