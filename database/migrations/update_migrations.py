import os
import re

mig_dir = r"c:\Users\DELL\Documents\BankManagement\BankSystem\database\migrations"

schemas = {
    "branches": """
            $table->id();
            $table->string('branch_name');
            $table->string('city');
            $table->string('address');
            $table->string('phone');
            $table->unsignedBigInteger('manager_id')->nullable(); // FK to staff
            $table->string('status')->default('active');
            $table->timestamp('opened_at')->nullable();
            $table->timestamps();
    """,
    "roles": """
            $table->id();
            $table->string('role_name');
            $table->string('tier');
            $table->string('description')->nullable();
            $table->decimal('txn_limit', 15, 2)->nullable();
            $table->boolean('branch_restricted')->default(true);
            $table->timestamps();
    """,
    "permissions": """
            $table->id();
            $table->string('permission_key')->unique();
            $table->string('module');
            $table->string('description')->nullable();
            $table->timestamps();
    """,
    "role_permissions": """
            $table->id();
            $table->foreignId('role_id')->constrained('roles')->onDelete('cascade');
            $table->foreignId('permission_id')->constrained('permissions')->onDelete('cascade');
            $table->timestamps();
    """,
    "staff": """
            $table->id();
            $table->string('full_name');
            $table->string('email')->unique();
            $table->string('password_hash');
            $table->foreignId('role_id')->constrained('roles');
            $table->foreignId('branch_id')->constrained('branches');
            $table->string('status')->default('active');
            $table->rememberToken();
            $table->timestamps();
    """,
    "customers": """
            $table->id();
            $table->string('full_name');
            $table->string('national_id')->unique();
            $table->string('phone');
            $table->text('address')->nullable();
            $table->string('kyc_status')->default('pending');
            $table->foreignId('home_branch_id')->constrained('branches');
            $table->timestamps();
    """,
    "account_types": """
            $table->id();
            $table->string('type_name');
            $table->decimal('interest_rate', 5, 2)->default(0);
            $table->decimal('min_balance', 15, 2)->default(0);
            $table->boolean('overdraft_allowed')->default(false);
            $table->decimal('withdrawal_limit', 15, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
    """,
    "accounts": """
            $table->id();
            $table->foreignId('customer_id')->constrained('customers');
            $table->foreignId('account_type_id')->constrained('account_types');
            $table->foreignId('home_branch_id')->constrained('branches');
            $table->string('account_number')->unique();
            $table->string('status')->default('active');
            $table->timestamp('opened_at')->nullable();
            $table->timestamps();
    """,
    "transactions": """
            $table->id();
            $table->string('reference')->unique();
            $table->string('type');
            $table->string('status');
            $table->decimal('amount', 15, 2);
            $table->unsignedBigInteger('initiated_by')->nullable();
            $table->foreign('initiated_by')->references('id')->on('staff');
            $table->foreignId('branch_id')->constrained('branches');
            $table->string('description')->nullable();
            $table->timestamps();
    """,
    "ledger_entries": """
            $table->id();
            $table->foreignId('transaction_id')->constrained('transactions');
            $table->foreignId('account_id')->constrained('accounts');
            $table->foreignId('branch_id')->constrained('branches');
            $table->enum('entry_type', ['debit', 'credit']);
            $table->decimal('amount', 15, 2);
            $table->decimal('balance_after', 15, 2);
            $table->index(['account_id', 'entry_type']);
            $table->timestamps();
    """,
    "pending_approvals": """
            $table->id();
            $table->foreignId('transaction_id')->constrained('transactions');
            $table->foreignId('requested_by')->constrained('staff');
            $table->foreignId('approver_role_id')->constrained('roles');
            $table->string('status')->default('pending');
            $table->unsignedBigInteger('decided_by')->nullable();
            $table->foreign('decided_by')->references('id')->on('staff');
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
    """,
    "inter_branch_clearing": """
            $table->id();
            $table->unsignedBigInteger('from_branch_id');
            $table->unsignedBigInteger('to_branch_id');
            $table->foreign('from_branch_id')->references('id')->on('branches');
            $table->foreign('to_branch_id')->references('id')->on('branches');
            $table->foreignId('transaction_id')->constrained('transactions');
            $table->decimal('amount', 15, 2);
            $table->string('status')->default('pending');
            $table->timestamp('settled_at')->nullable();
            $table->timestamps();
    """,
    "loans": """
            $table->id();
            $table->foreignId('customer_id')->constrained('customers');
            $table->foreignId('account_id')->constrained('accounts');
            $table->decimal('amount', 15, 2);
            $table->decimal('interest_rate', 5, 2);
            $table->integer('term_months');
            $table->string('status')->default('applied');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->foreign('approved_by')->references('id')->on('staff');
            $table->timestamp('disbursed_at')->nullable();
            $table->timestamps();
    """,
    "loan_repayments": """
            $table->id();
            $table->foreignId('loan_id')->constrained('loans');
            $table->date('due_date');
            $table->decimal('amount', 15, 2);
            $table->timestamp('paid_at')->nullable();
            $table->unsignedBigInteger('ledger_entry_id')->nullable();
            $table->foreign('ledger_entry_id')->references('id')->on('ledger_entries');
            $table->string('status')->default('pending');
            $table->timestamps();
    """,
    "kyc_documents": """
            $table->id();
            $table->foreignId('customer_id')->constrained('customers');
            $table->string('doc_type');
            $table->string('file_path');
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->foreign('verified_by')->references('id')->on('staff');
            $table->string('status')->default('pending');
            $table->timestamp('uploaded_at')->nullable();
            $table->timestamps();
    """,
    "notifications": """
            $table->id();
            $table->unsignedBigInteger('recipient_id');
            $table->string('recipient_type');
            $table->string('channel');
            $table->text('message');
            $table->timestamp('sent_at')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
    """,
    "staff_audit_log": """
            $table->id();
            $table->foreignId('staff_id')->constrained('staff');
            $table->string('action');
            $table->string('permission_used')->nullable();
            $table->string('target_table')->nullable();
            $table->unsignedBigInteger('target_id')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('result');
            $table->timestamps();
    """
}

for root, dirs, files in os.walk(mig_dir):
    for f in files:
        for t_name, schema in schemas.items():
            if f.endswith(f"create_{t_name}_table.php"):
                p = os.path.join(root, f)
                with open(p, 'r') as file:
                    content = file.read()
                
                # Use a more exact replacement string
                match_str = f"Schema::create('{t_name}', function (Blueprint $table) {{\n            $table->id();\n            $table->timestamps();\n        }});"
                replacement_str = f"Schema::create('{t_name}', function (Blueprint $table) {{\n{schema}\n        }});"
                
                new_content = content.replace(match_str, replacement_str)
                
                with open(p, 'w') as file:
                    file.write(new_content)
                print(f"Updated {f}")
