<x-mail::message>
# Hi {{ $recipientName ?: 'there' }},

Thank you for signing up to help test **Tsiyon** before our public launch! Your early feedback will directly shape how the app feels for everyone.

To get started on your Android device, please follow these four quick steps:

1. **Use Your Invited Gmail Account:** Open the Google Play Store app on your phone and confirm you are signed in with the **exact Gmail address** that received this email. *(If you see an "App not available" message in the next step, switching to your invited Gmail account in the Play Store fixes it 99% of the time).*
2. **Accept the Beta Invite:** Tap the button below to open the Google Play testing page and select **Become a Tester**.

<x-mail::button :url="$optInUrl">
Join the Beta & Install
</x-mail::button>

3. **Install the App:** Once the page refreshes to show the welcome screen, tap **Download it on Google Play** to install Tsiyon.
4. **Keep Installed for 14 Days:** Google requires our testers to stay opted in and keep the app installed for **14 consecutive days** before approving our public release.

### How to Send Feedback
Whenever you spot a bug or have a suggestion, go to **Settings > Feedback** inside Tsiyon — your phone model and app version will fill in automatically so you don't have to type them out.

Send an email to support@tsiyonapp.com anytime if you hit a snag getting installed. Grateful to have you on board!

Warmly,  
**The Tsiyon Team**

<x-mail::subcopy>
You're receiving this email because you signed up for the Tsiyon early access waitlist. If you no longer wish to participate, simply send an email to support@tsiyonapp.com to opt out.
</x-mail::subcopy>
</x-mail::message>
