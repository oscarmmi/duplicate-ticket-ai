<?php

namespace Database\Seeders;

use App\Models\SupportTicket;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $tickets = [
            ['subject' => 'Checkout tax is charged twice on EU orders', 'body' => 'When customers place an EU order, checkout calculates the sales tax twice and the final total is too high. The order summary shows two tax lines for the same purchase.', 'status' => 'open'],
            ['subject' => 'EU checkout applies sales tax two times', 'body' => 'The checkout page adds sales tax twice for European orders. Customers see duplicate tax in the order total even though the confirmation email has one tax line.', 'status' => 'open'],
            ['subject' => 'Password reset link expires immediately', 'body' => 'The password reset email contains a link that is already expired when opened. Customers cannot reset a password and must request another email.', 'status' => 'open'],
            ['subject' => 'Password reset URL says it has expired', 'body' => 'Users click the password reset link from their email and immediately see an expired message. The reset token is rejected before they can choose a new password.', 'status' => 'open'],
            ['subject' => 'Mobile app crashes after receipt upload', 'body' => 'Uploading a receipt from the mobile app closes the app before the document finishes processing. The receipt is not attached to the expense report.', 'status' => 'open'],
            ['subject' => 'Receipt upload makes the mobile app crash', 'body' => 'The app crashes on mobile devices while a receipt upload is processing. Users lose the attachment and have to reopen the expense report to try again.', 'status' => 'open'],
            ['subject' => 'Exported report is missing the date column', 'body' => 'The monthly CSV export leaves the transaction date column blank even though dates are visible in the dashboard table.', 'status' => 'open'],
            ['subject' => 'Cannot change notification preferences', 'body' => 'Saving email notification preferences returns a success message, but the account settings revert to the previous selection after refreshing.', 'status' => 'open'],
            ['subject' => 'Search results ignore the selected project', 'body' => 'Searching from a project page includes tasks from other projects instead of limiting results to the selected workspace.', 'status' => 'closed'],
            ['subject' => 'Two-factor setup code is not accepted', 'body' => 'The authenticator code is rejected during two-factor setup despite being current. The account owner cannot finish enabling the security setting.', 'status' => 'open'],
        ];

        foreach ($tickets as $ticket) {
            SupportTicket::query()->updateOrCreate(['subject' => $ticket['subject']], $ticket);
        }
    }
}
