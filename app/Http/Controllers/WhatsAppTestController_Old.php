<?phpnamespace App\Http\Controllers;use Illuminate\Http\Request;use App\Services\WhatsAppService;use App\Models\User;use App\Models\PanicReport;use Illuminate\Support\Facades\Log;class WhatsAppTestController extends Controller{    protected $whatsAppService;    public function __construct(WhatsAppService $whatsAppService)    {        $this->whatsAppService = $whatsAppService;    }    /**     * Test basic WhatsApp functionality     */    public function testBasic(Request $request)    {        $request->validate([            'phone' => 'required|string',            'message' => 'required|string'        ]);        $result = $this->whatsAppService->sendMessage(            $request->phone,             $request->message        );        return response()->json([            'test' => 'basic_whatsapp_send',            'result' => $result,            'formatted_phone' => $this->whatsAppService->formatPhoneNumber($request->phone)        ]);    }    /**     * Test emergency alert simulation     */    public function testEmergencyAlert()    {        // Get first panic report or create dummy data        $panicReport = PanicReport::with('user')->first();                if (!$panicReport) {            // Create dummy panic data for testing            $dummyPanic = new PanicReport([                'latitude' => -7.983908,                'longitude' => 112.621391,                'description' => 'Test emergency alert',                'status' => 'pending',                'created_at' => now()            ]);                        $dummyUser = new User([                'name' => 'Test User',                'no_telp' => '081234567890'            ]);                        $dummyPanic->user = $dummyUser;            $panicReport = $dummyPanic;        }        // Get volunteers with phone numbers        $volunteers = User::where('role', 'relawan')            ->whereNotNull('no_telp')            ->get();        if ($volunteers->isEmpty()) {            return response()->json([                'error' => 'No volunteers with phone numbers found for testing',                'suggestion' => 'Add phone numbers to volunteers in database'            ], 404);        }        $result = $this->whatsAppService->sendEmergencyAlert($panicReport, $volunteers);        return response()->json([            'test' => 'emergency_alert_simulation',            'panic_report' => [                'location' => [$panicReport->latitude, $panicReport->longitude],                'description' => $panicReport->description,                'reporter' => $panicReport->user->name ?? 'Unknown'            ],            'volunteers_notified' => $volunteers->count(),            'volunteers' => $volunteers->map(function($v) {                return [                    'name' => $v->name,                    'phone' => $v->no_telp                ];            }),            'result' => $result        ]);    }    /**     * Test bulk message     */    public function testBulkMessage(Request $request)    {        $volunteers = User::where('role', 'relawan')            ->whereNotNull('no_telp')            ->get();        if ($volunteers->isEmpty()) {            return response()->json([                'error' => 'No volunteers with phone numbers found',            ], 404);        }        $targets = $volunteers->map(function($volunteer) {            return [                'phone' => $volunteer->no_telp,                'name' => $volunteer->name,                'role' => 'Relawan'            ];        })->toArray();        $message = $request->get('message',             "🧪 *TEST BULK MESSAGE - SIGAP UNDIP* 🧪\n\n" .
            "Halo {name}!\n\n" .
            "Ini adalah pesan test untuk sistem notifikasi WhatsApp.\n" .
            "Role Anda: {var1}\n\n" .
            "Jika Anda menerima pesan ini, berarti sistem WhatsApp sudah berfungsi dengan baik! ✅\n\n" .
            "_Test dari Sistem SIGAP UNDIP_"
        );

        $result = $this->whatsAppService->sendBulkMessage($targets, $message);

        return response()->json([
            'test' => 'bulk_message',
            'targets_count' => count($targets),
            'targets' => $targets,
            'message' => $message,
            'result' => $result
        ]);
    }

    /**
     * Check Fonnte account info
     */
    public function checkAccount()
    {
        $accountInfo = $this->whatsAppService->getAccountInfo();
        
        return response()->json([
            'test' => 'account_info',
            'configured' => $this->whatsAppService->isConfigured(),
            'account_info' => $accountInfo
        ]);
    }

    /**
     * Test shift reminder
     */
    public function testShiftReminder(Request $request)
    {
        $request->validate([
            'volunteer_id' => 'required|exists:users,id'
        ]);

        $volunteer = User::findOrFail($request->volunteer_id);
        
        if (!$volunteer->no_telp) {
            return response()->json([
                'error' => 'Volunteer has no phone number'
            ], 400);
        }

        // Create dummy shift data
        $dummyShift = (object) [
            'tanggal_shift' => now()->addDay()->format('Y-m-d'),
            'jam_mulai' => '08:00',
            'jam_selesai' => '16:00'
        ];

        $result = $this->whatsAppService->sendShiftReminder($volunteer, $dummyShift);

        return response()->json([
            'test' => 'shift_reminder',
            'volunteer' => [
                'name' => $volunteer->name,
                'phone' => $volunteer->no_telp
            ],
            'shift_info' => $dummyShift,
            'result' => $result
        ]);
    }

    /**
     * Send custom message for testing
     */
    public function sendCustom(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'message' => 'required|string',
            'delay' => 'nullable|integer|min:1|max:10',
            'typing' => 'nullable|boolean'
        ]);

        $options = [];
        if ($request->has('delay')) {
            $options['delay'] = $request->delay;
        }
        if ($request->has('typing')) {
            $options['typing'] = $request->typing;
        }

        $result = $this->whatsAppService->sendMessage(
            $request->phone,
            $request->message,
            $options
        );

        return response()->json([
            'test' => 'custom_message',
            'phone' => $request->phone,
            'message' => $request->message,
            'options' => $options,
            'result' => $result
        ]);
    }

    /**
     * Get volunteers with phone numbers
     */
    public function getVolunteersWithPhone()
    {
        $volunteers = User::where('role', 'relawan')
            ->whereNotNull('no_telp')
            ->select('id', 'name', 'email', 'no_telp')
            ->get();

        $volunteersWithoutPhone = User::where('role', 'relawan')
            ->whereNull('no_telp')
            ->select('id', 'name', 'email')
            ->get();

        return response()->json([
            'volunteers_with_phone' => $volunteers,
            'volunteers_without_phone' => $volunteersWithoutPhone,
            'summary' => [
                'total_volunteers' => User::where('role', 'relawan')->count(),
                'with_phone' => $volunteers->count(),
                'without_phone' => $volunteersWithoutPhone->count()
            ]
        ]);
    }
}
