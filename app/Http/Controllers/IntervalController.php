<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Jenis;
use App\Models\Produksi;

class IntervalController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
    //

    public function menentukanInt(Request $request)
    {
        $defaultJenisId = 1;
        $jenisId = $request->input('jenis_id', $defaultJenisId);

        // // Ambil tahun terkecil dari database
        // $smallestYear = Produksi::min('tahun');
        // $currentYear = date('Y'); // Get current year

        // // Default tahun diambil sebagai tahun terkecil jika tidak ada input tahun dari request
        // $tahun = $request->input('tahun', $smallestYear ?: $currentYear);

        // Ambil data produksi berdasarkan filter jenis_id jika diberikan
        $produksiQuery = Produksi::when($jenisId, function ($query, $jenisId) {
            return $query->where('jenis_id', $jenisId);
        });
        // ->when($tahun, function ($query, $tahun) {
        //     return $query->where('tahun', $tahun);
        // });

        $produksi = $produksiQuery->get();

        $total = $produksi->sum('volume'); // Hitung total volume produksi

        // Inisialisasi array untuk menyimpan hasil perhitungan
        $probability = [];
        $cumulative = [];
        $botInterval = [];
        $topInterval = [];

        $amount = $produksi->count();

        // Menghitung peluang/kemungkinan terjadinya permintaan dan nilai kumulatif
        $cumulativeSum = 0;
        $jumlahProbabilitas = 0;

        foreach ($produksi as $index => $item) {
            $probability[$index] = round($item->volume / $total, 3);
            $cumulativeSum += $probability[$index];
            $cumulative[$index] = round($cumulativeSum, 3);
            $jumlahProbabilitas += $probability[$index];
        }

        // Menentukan interval bawah dan atas pertama
        $botInterval[0] = 0; // Dimulai dari 0
        $topInterval[0] = round($cumulative[0] * 100, 0); // Interval akhir pertama dihitung dari kumulatif pertama

        // Menentukan interval bawah dan atas
        for ($i = 1; $i < $amount; $i++) {
            $botInterval[$i] = $topInterval[$i - 1] + 1; // Interval bawah sama dengan interval atas sebelumnya + 1
            $topInterval[$i] = round($cumulative[$i] * 100, 0); // Interval akhir dihitung dari kumulatif dan diakhiri dengan 100
        }

        // Ambil semua jenis untuk dropdown filter
        $jenisList = Jenis::all();

        // Mengambil daftar tahun produksi yang tersedia
        // $yearsList = Produksi::select('tahun')
        //     ->distinct()
        //     ->orderBy('tahun', 'desc')
        //     ->pluck('tahun');

        // Menyimpan data hasil perhitungan ke dalam view
        return view('interval.index', [
            'produksi'              => $produksi,
            'total'                 => $total,
            'amount'                => $amount,
            'probability'           => $probability,
            'cumulative'            => $cumulative,
            'topInterval'           => $topInterval,
            'botInterval'           => $botInterval,
            'jumlahProbabilitas'    => $jumlahProbabilitas,
            'jenisList'             => $jenisList,
            'jenisId'               => $jenisId,
            // 'tahun'                 => $tahun,
            // 'yearsList'             => $yearsList,
            // 'smallestYear'  => $smallestYear,
        ]);
    }

    public function random(Request $request)
    {
        $defaultJenisId = 1; // Default to jenis_id 1 if none is provided
        $jenisId = $request->input('jenis_id', $defaultJenisId);

        // Ambil tahun terkecil dari database
        $smallestYear = Produksi::min('tahun');
        $currentYear = date('Y'); // Get current year

        // Default tahun diambil sebagai tahun terkecil jika tidak ada input tahun dari request
        $tahun = $request->input('tahun', $smallestYear ?: $currentYear);

        // Ambil data produksi berdasarkan filter jenis_id
        $produksi = Produksi::where('jenis_id', $jenisId)->where('tahun', $tahun)->get();

        $total = $produksi->sum('volume'); // Hitung total volume produksi

        // Inisialisasi array untuk menyimpan hasil perhitungan
        $probability = [];
        $cumulative = [];
        $botInterval = [];
        $topInterval = [];

        $amount = $produksi->count();

        // Menghitung peluang/kemungkinan terjadinya permintaan dan nilai kumulatif
        $cumulativeSum = 0;

        foreach ($produksi as $index => $item) {
            $probability[$index] = round($item->volume / $total, 3);
            $cumulativeSum += $probability[$index];
            $cumulative[$index] = round($cumulativeSum, 3);
        }

        // Menentukan interval bawah dan atas pertama
        $botInterval[0] = 0; // Dimulai dari 0
        $topInterval[0] = round($cumulative[0] * 100, 0); // Interval akhir pertama dihitung dari kumulatif pertama

        // Menentukan interval bawah dan atas
        for ($i = 1; $i < $amount; $i++) {
            $botInterval[$i] = $topInterval[$i - 1] + 1; // Interval bawah sama dengan interval atas sebelumnya + 1
            $topInterval[$i] = round($cumulative[$i] * 100, 0); // Interval akhir dihitung dari kumulatif dan diakhiri dengan 100
        }

        // Ambil semua jenis untuk dropdown filter
        $jenisList = Jenis::all();

        // Mengambil daftar tahun produksi yang tersedia
        $yearsList = Produksi::select('tahun')
            ->distinct()
            ->orderBy('tahun', 'asc')
            ->pluck('tahun');

        // Menyimpan data hasil perhitungan ke dalam view
        return view('rand.index', [
            'produksi'      => $produksi,
            'total'         => $total,
            'amount'        => $amount,
            'probability'   => $probability,
            'cumulative'    => $cumulative,
            'topInterval'   => $topInterval,
            'botInterval'   => $botInterval,
            'jenisList'     => $jenisList,
            'jenisId'       => $jenisId,
            'tahun'         => $tahun,
            'yearsList'     => $yearsList,
            'smallestYear'  => $smallestYear,
        ]);
    }

    public function hasil(Request $request)
    {
        $jmlsim = $request->input('jmlsim', 4); // Jumlah simulasi defaultnya adalah 4
        $jenisId = $request->input('jenis_id');
        $currentYear = date('Y');
        $tahun = $request->input('tahun', $currentYear);

        // Ambil data produksi dari tahun 2019 sampai tahun yang dipilih
        $startYear = 2019;
        $historicalYears = range($startYear, $tahun - 1);
        $historicalProduksi = Produksi::where('jenis_id', $jenisId)
            ->whereIn('tahun', $historicalYears)
            ->orderBy('tahun', 'asc')
            ->orderBy('periode', 'asc')
            ->get();

        $total = $historicalProduksi->sum('volume');
        $angka_random = [];
        $botInterval = [];
        $topInterval = [];
        $cumulative = [];

        // Menghitung peluang/kemungkinan terjadinya permintaan dan nilai kumulatif
        $cumulativeSum = 0;
        foreach ($historicalProduksi as $index => $item) {
            $probability[$index] = round($item->volume / $total, 3);
            $cumulativeSum += $probability[$index];
            $cumulative[$index] = round($cumulativeSum, 3);
        }

        // Menentukan interval bawah dan atas pertama
        $botInterval[0] = 0;
        $topInterval[0] = round($cumulative[0] * 100, 0);
        for ($i = 1; $i < count($historicalProduksi); $i++) {
            $botInterval[$i] = $topInterval[$i - 1] + 1;
            $topInterval[$i] = round($cumulative[$i] * 100, 0);
        }

        $demandResult = [];
        for ($i = 0; $i < $jmlsim; $i++) {
            $randomNumber = rand(1, 100);
            for ($j = 0; $j < count($topInterval); $j++) {
                if ($randomNumber >= $botInterval[$j] && $randomNumber <= $topInterval[$j]) {
                    $demandResult[$i] = $historicalProduksi[$j]->volume;
                    break;
                }
            }
            if (!isset($demandResult[$i])) {
                $demandResult[$i] = 0;
            }
            $angka_random[$i] = $randomNumber;
        }

        // Ambil data real untuk tahun yang diprediksi
        $dataReal = Produksi::where('jenis_id', $jenisId)
            ->where('tahun', $tahun)
            ->orderBy('periode')
            ->pluck('volume')
            ->toArray();

        // Merge historical data and forecast data
        $mergedData = array_merge($historicalProduksi->pluck('volume')->toArray(), $dataReal);

        return view('hasil.index', [
            'jmlsim'        => $jmlsim,
            'angka_random'  => $angka_random,
            'topInterval'   => $topInterval,
            'botInterval'   => $botInterval,
            'demandResult'  => $demandResult,
            'historicalData' => $historicalProduksi->pluck('volume')->toArray(),
            'dataReal'      => $dataReal,
            'mergedData'    => $mergedData,
            'startYear'     => $startYear,
            'tahun'         => $tahun
        ]);
    }
}
