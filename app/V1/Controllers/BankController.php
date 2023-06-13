<?php


namespace App\V1\Controllers;


use App\Bank;
use App\V1\Models\BankModel;
use App\V1\Transformers\Bank\BankTransformer;
use Illuminate\Http\Request;

class BankController extends BaseController
{
    /**
     * @var Bank
     */
    protected $model;

    /**
     * BankController constructor.
     */
    public function __construct()
    {
        $this->model = new BankModel();
    }

    public function getListBank1(Request $request)
    {
        $input = $request->all();
        $limit = array_get($input, 'limit', 20);
        if (!empty($input['name'])) {
            $input['name'] = ['like' => "%{$input['name']}%"];
        }
        $result = $this->model->search($input, [], $limit);
        return $this->response->paginator($result, new BankTransformer());
    }

    public function getListBank()
    {
        $banks = [
            [
                'code'        => 'AGRIBANK',
                'name'        => 'NGÂN HÀNG NNO&PT NÔNG THÔN VN (AGRIBANK)',
                'description' => '',
            ],
            [
                'code'        => 'BIDV',
                'name'        => 'NGÂN HÀNG ĐẦU TƯ VÀ PHÁT TRIỂN VN (BIDV)',
                'description' => '',
            ],
            [
                'code'        => 'VIETINBANK',
                'name'        => 'NGÂN HÀNG CÔNG THƯƠNG VN (VIETINBANK)',
                'description' => '',
            ],
            [
                'code'        => 'VPBANK',
                'name'        => 'NGÂN HÀNG VIỆT NAM THỊNH VƯỢNG (VP BANK)',
                'description' => '',
            ],
            [
                'code'        => 'ACB',
                'name'        => 'NGÂN HÀNG Á CHÂU (ACB)',
                'description' => '',
            ],
            [
                'code'        => 'ABBANK',
                'name'        => 'NGÂN HÀNG AN BÌNH (ABBANK)',
                'description' => '',
            ],
            [
                'code'        => 'BACABANK',
                'name'        => 'NGÂN HÀNG BẮC Á (BAC A BANK)',
                'description' => '',
            ],
            [
                'code'        => 'VIET-CAPITAL-BANK',
                'name'        => 'NGÂN HÀNG BẢN VIỆT (VIET CAPITAL BANK)',
                'description' => '',
            ],
            [
                'code'        => 'BAO-VIET-BANK',
                'name'        => 'NGÂN HÀNG BẢO VIỆT (BAO VIET BANK)',
                'description' => '',
            ],
            [
                'code'        => 'LIEN-VIET-POST-BANK',
                'name'        => 'NGÂN HÀNG BƯU ĐIỆN LIÊN VIỆT (LIEN VIET POST BANK)',
                'description' => '',
            ],
            [
                'code'        => 'CIMB-BANK',
                'name'        => 'NGÂN HÀNG CIMB BANK',
                'description' => '',
            ],
            [
                'code'        => 'PVCOMBANK',
                'name'        => 'NGÂN HÀNG ĐẠI CHÚNG (PVCOMBANK)',
                'description' => '',
            ],
            [
                'code'        => 'OCEANBANK',
                'name'        => 'NGÂN HÀNG ĐẠI DƯƠNG (OCEANBANK)',
                'description' => '',
            ],
            [
                'code'        => 'GPBANK',
                'name'        => 'NGÂN HÀNG DẦU KHÍ TOÀN CẦU (GPBANK)',
                'description' => '',
            ],
            [
                'code'        => 'DONG-A-BANK',
                'name'        => 'NGÂN HÀNG ĐÔNG Á (DONG A BANK)',
                'description' => '',
            ],
            [
                'code'        => 'SEABANK',
                'name'        => 'NGÂN HÀNG ĐÔNG NAM Á (SEABANK)',
                'description' => '',
            ],
            [
                'code'        => 'MARITIME-BANK',
                'name'        => 'NGÂN HÀNG HÀNG HẢI (MARITIME BANK)',
                'description' => '',
            ],
            [
                'code'        => 'HONG-LEONG-VN',
                'name'        => 'NGÂN HÀNG HONG LEONG VN',
                'description' => '',
            ],
            [
                'code'        => 'INDOVINA-BANK',
                'name'        => 'NGÂN HÀNG INDOVINA BANK',
                'description' => '',
            ],
            [
                'code'        => 'INDUSTRIAL-BANK-OF-KOREA',
                'name'        => 'NGÂN HÀNG INDUSTRIAL BANK OF KOREA',
                'description' => '',
            ],
            [
                'code'        => 'KIEN-LONG-BANK',
                'name'        => 'NGÂN HÀNG KIÊN LONG (KIEN LONG BANK)',
                'description' => '',
            ],
            [
                'code'        => 'TECHCOMBANK',
                'name'        => 'NGÂN HÀNG KỸ THƯƠNG VN (TECHCOMBANK)',
                'description' => '',
            ],
            [
                'code'        => 'VRB',
                'name'        => 'NGÂN HÀNG LIÊN DOANH VIỆT NGA (VRB)',
                'description' => '',
            ],
            [
                'code'        => 'NAM-A-BANK',
                'name'        => 'NGÂN HÀNG NAM Á (NAM A BANK)',
                'description' => '',
            ],
            [
                'code'        => 'CBBank',
                'name'        => 'NGÂN HÀNG Ngân hàng Xây dựng (CBBank)',
                'description' => '',
            ],
            [
                'code'        => 'Nonghyup-HN',
                'name'        => 'NGÂN HÀNG Nonghyup - Chi nhánh Hà Nội (NHB - Chi nhánh Hà Nội)',
                'description' => '',
            ],
            [
                'code'        => 'HD-BANK',
                'name'        => 'NGÂN HÀNG PHÁT TRIỂN TP HCM (HD BANK)',
                'description' => '',
            ],
            [
                'code'        => 'OCB',
                'name'        => 'NGÂN HÀNG PHƯƠNG ĐÔNG (OCB)',
                'description' => '',
            ],
            [
                'code'        => 'PUBLIC-BANK-VN',
                'name'        => 'NGÂN HÀNG PUBLIC BANK VN',
                'description' => '',
            ],
            [
                'code'        => 'MB',
                'name'        => 'NGÂN HÀNG QUÂN ĐỘI (MB)',
                'description' => '',
            ],
            [
                'code'        => 'NCB',
                'name'        => 'NGÂN HÀNG QUỐC DÂN (NCB)',
                'description' => '',
            ],
            [
                'code'        => 'VIB',
                'name'        => 'NGÂN HÀNG QUỐC TẾ (VIB)',
                'description' => '',
            ],
            [
                'code'        => 'SCB',
                'name'        => 'NGÂN HÀNG SÀI GÒN (SCB)',
                'description' => '',
            ],
            [
                'code'        => 'SHB',
                'name'        => 'NGÂN HÀNG SÀI GÒN - HÀ NỘI (SHB)',
                'description' => '',
            ],
            [
                'code'        => 'SAIGONBANK',
                'name'        => 'NGÂN HÀNG SÀI GÒN CÔNG THƯƠNG (SAIGONBANK)',
                'description' => '',
            ],
            [
                'code'        => 'SACOMBANK',
                'name'        => 'NGÂN HÀNG SÀI GÒN THƯƠNG TÍN (SACOMBANK)',
                'description' => '',
            ],
            [
                'code'        => 'SHINHAN',
                'name'        => 'NGÂN HÀNG SHINHAN VN',
                'description' => '',
            ],
            [
                'code'        => 'TIEN-PHONG-BANK',
                'name'        => 'NGÂN HÀNG TIỀN PHONG (TIEN PHONG BANK)',
                'description' => '',
            ],
            [
                'code'        => 'HSBC',
                'name'        => 'NGÂN HÀNG TNHH MTV HSBC Việt Nam (HSBC)',
                'description' => '',
            ],
            [
                'code'        => 'SCVN',
                'name'        => 'NGÂN HÀNG TNHH MTV Standard Chartered Việt Nam (SCVN)',
                'description' => '',
            ],
            [
                'code'        => 'UOB-VN',
                'name'        => 'NGÂN HÀNG UOB VIETNAM (UOB VN)',
                'description' => '',
            ],
            [
                'code'        => 'VIET-A-BANK',
                'name'        => 'NGÂN HÀNG VIỆT Á (VIET A BANK)',
                'description' => '',
            ],
            [
                'code'        => 'VIETBANK',
                'name'        => 'NGÂN HÀNG VIỆT NAM THƯƠNG TÍN (VIETBANK)',
                'description' => '',
            ],
            [
                'code'        => 'WOORI-BANK',
                'name'        => 'NGÂN HÀNG WOORI BANK VIET NAM',
                'description' => '',
            ],
            [
                'code'        => 'PGBANK',
                'name'        => 'NGÂN HÀNG XĂNG DẦU PETROLIMEX (PGBANK)',
                'description' => '',
            ],
            [
                'code'        => 'EXIMBANK',
                'name'        => 'NGÂN HÀNG XUẤT NHẬP KHẨU (EXIMBANK)',
                'description' => '',
            ],
            [
                'code'        => 'VIETCOMBANK',
                'name'        => 'NGÂN HÀNG THƯƠNG MẠI CỔ PHẦN (VIETCOMBANK)',
                'description' => '',
            ],
        ];

        return $this->responseData($banks);
    }

    public function list(Request $request)
    {
        $data = [
            'data' => [
                'BUSAN'                                                       => [
                    'code' => '',
                    'name' => 'BUSAN',
                ],
                'BANK OF INDIA'                                               => [
                    'code' => '',
                    'name' => 'BANK OF INDIA',
                ],
                'E.SUN'                                                       => [
                    'code' => '',
                    'name' => 'E.SUN',
                ],
                'KY THUONG VN (TECHCOMBANK)'                                  => [
                    'code' => 'TECHCOMBANK',
                    'name' => 'KY THUONG VN',
                ],
                'VIET NAM THUONG TIN (VIETBANK)'                              => [
                    'code' => 'VIETBANK',
                    'name' => 'VIET NAM THUONG TIN',
                ],
                'SHINHAN VN'                                                  => [
                    'code' => '',
                    'name' => 'SHINHAN VN',
                ],
                'HONGKONG AND SHANGHAI BANK (HSBC)'                           => [
                    'code' => 'HSBC',
                    'name' => 'HONGKONG AND SHANGHAI BANK',
                ],
                'SAI GON THUONG TIN (SACOMBANK)'                              => [
                    'code' => 'SACOMBANK',
                    'name' => 'SAI GON THUONG TIN',
                ],
                'DBS BANK LTD CN HCM'                                         => [
                    'code' => '',
                    'name' => 'DBS BANK LTD CN HCM',
                ],
                'UOB VIETNAM (UOB VN)'                                        => [
                    'code' => 'UOB VN',
                    'name' => 'UOB VIETNAM',
                ],
                'NNO&PT NONG THON VN (AGRIBANK)'                              => [
                    'code' => 'AGRIBANK',
                    'name' => 'NNO&PT NONG THON VN',
                ],
                'SAI GON (SCB)'                                               => [
                    'code' => 'SCB',
                    'name' => 'SAI GON',
                ],
                'DONG A (DONG A BANK)'                                        => [
                    'code' => 'DONG A BANK',
                    'name' => 'DONG A',
                ],
                'BAN VIET (VIET CAPITAL BANK)'                                => [
                    'code' => 'VIET CAPITAL BANK',
                    'name' => 'BAN VIET',
                ],
                'BUU DIEN LIEN VIET (LIEN VIET POST BANK)'                    => [
                    'code' => 'LIEN VIET POST BANK',
                    'name' => 'BUU DIEN LIEN VIET',
                ],
                'SIAM COMMERCIAL BANK PUBLIC COMPANY LTD'                     => [
                    'code' => '',
                    'name' => 'SIAM COMMERCIAL BANK PUBLIC COMPANY LTD',
                ],
                'MAY BANK (HN)'                                               => [
                    'code' => 'HN',
                    'name' => 'MAY BANK',
                ],
                'BANK OF CHINA'                                               => [
                    'code' => '',
                    'name' => 'BANK OF CHINA',
                ],
                'JP MORGAN CHASE BANK'                                        => [
                    'code' => '',
                    'name' => 'JP MORGAN CHASE BANK',
                ],
                'SUMITOMO MITSUI BANKING CORPORATION'                         => [
                    'code' => '',
                    'name' => 'SUMITOMO MITSUI BANKING CORPORATION',
                ],
                'BNP PARIBAS CHI NHANH HN'                                    => [
                    'code' => '',
                    'name' => 'BNP PARIBAS CHI NHANH HN',
                ],
                'CONG THUONG VN (VIETINBANK)'                                 => [
                    'code' => 'VIETINBANK',
                    'name' => 'CONG THUONG VN',
                ],
                'XUAT NHAP KHAU (EXIMBANK)'                                   => [
                    'code' => 'EXIMBANK',
                    'name' => 'XUAT NHAP KHAU',
                ],
                'SAI GON CONG THUONG (SAIGONBANK)'                            => [
                    'code' => 'SAIGONBANK',
                    'name' => 'SAI GON CONG THUONG',
                ],
                'VIET NAM THINH VUONG (VP BANK)'                              => [
                    'code' => 'VP BANK',
                    'name' => 'VIET NAM THINH VUONG',
                ],
                'BANGKOK BANK HA NOI'                                         => [
                    'code' => '',
                    'name' => 'BANGKOK BANK HA NOI',
                ],
                'QUAN DOI (MB)'                                               => [
                    'code' => 'MB',
                    'name' => 'QUAN DOI',
                ],
                'DAI DUONG (OCEANBANK)'                                       => [
                    'code' => 'OCEANBANK',
                    'name' => 'DAI DUONG',
                ],
                'DAU KHI TOAN CAU (GPBANK)'                                   => [
                    'code' => 'GPBANK',
                    'name' => 'DAU KHI TOAN CAU',
                ],
                'DONG NAM A (SEABANK)'                                        => [
                    'code' => 'SEABANK',
                    'name' => 'DONG NAM A',
                ],
                'XANG DAU PETROLIMEX (PGBANK)'                                => [
                    'code' => 'PGBANK',
                    'name' => 'XANG DAU PETROLIMEX',
                ],
                'SAI GON - HA NOI (SHB)'                                      => [
                    'code' => 'SHB',
                    'name' => 'SAI GON - HA NOI',
                ],
                'TIEN PHONG (TIEN PHONG BANK)'                                => [
                    'code' => 'TIEN PHONG BANK',
                    'name' => 'TIEN PHONG',
                ],
                'CITI BANK HN'                                                => [
                    'code' => '',
                    'name' => 'CITI BANK HN',
                ],
                'HANG HAI (MARITIME BANK)'                                    => [
                    'code' => 'MARITIME BANK',
                    'name' => 'HANG HAI',
                ],
                'QUOC DAN (NCB)'                                              => [
                    'code' => 'NCB',
                    'name' => 'QUOC DAN',
                ],
                'OVERSEA-CHINESE BANKING CORPORATION LTD'                     => [
                    'code' => '',
                    'name' => 'OVERSEA-CHINESE BANKING CORPORATION LTD',
                ],
                'CHINA CONSTRUCTION BANK CORPORATION'                         => [
                    'code' => '',
                    'name' => 'CHINA CONSTRUCTION BANK CORPORATION',
                ],
                'CIMB BANK'                                                   => [
                    'code' => '',
                    'name' => 'CIMB BANK',
                ],
                'CHINH SACH XA HOI (VBSP)'                                    => [
                    'code' => 'VBSP',
                    'name' => 'CHINH SACH XA HOI',
                ],
                'XAY DUNG VN (CP BANK)'                                       => [
                    'code' => 'CP BANK',
                    'name' => 'XAY DUNG VN',
                ],
                'AN BINH (ABBANK)'                                            => [
                    'code' => 'ABBANK',
                    'name' => 'AN BINH',
                ],
                'A CHAU (ACB)'                                                => [
                    'code' => 'ACB',
                    'name' => 'A CHAU',
                ],
                'PHUONG DONG (OCB)'                                           => [
                    'code' => 'OCB',
                    'name' => 'PHUONG DONG',
                ],
                'BAO VIET (BAO VIET BANK)'                                    => [
                    'code' => 'BAO VIET BANK',
                    'name' => 'BAO VIET',
                ],
                'NAM A (NAM A BANK)'                                          => [
                    'code' => 'NAM A BANK',
                    'name' => 'NAM A',
                ],
                'WOORI BANK VIET NAM'                                         => [
                    'code' => '',
                    'name' => 'WOORI BANK VIET NAM',
                ],
                'BANGKOK BANK HCM'                                            => [
                    'code' => '',
                    'name' => 'BANGKOK BANK HCM',
                ],
                'CITI BANK'                                                   => [
                    'code' => '',
                    'name' => 'CITI BANK',
                ],
                'PUBLIC BANK VN'                                              => [
                    'code' => '',
                    'name' => 'PUBLIC BANK VN',
                ],
                'BPCE IOM'                                                    => [
                    'code' => '',
                    'name' => 'BPCE IOM',
                ],
                'FIRST COMMERCIAL BANK HANOI'                                 => [
                    'code' => '',
                    'name' => 'FIRST COMMERCIAL BANK HANOI',
                ],
                'MIZUHO CORPORATE BANK LTD., HN'                              => [
                    'code' => '',
                    'name' => 'MIZUHO CORPORATE BANK LTD., HN',
                ],
                'BANK OF COMMUNICATIONS'                                      => [
                    'code' => '',
                    'name' => 'BANK OF COMMUNICATIONS',
                ],
                'DEUTSCHE BANK'                                               => [
                    'code' => '',
                    'name' => 'DEUTSCHE BANK',
                ],
                'CTBC (HNTM CHINA TRUST)'                                     => [
                    'code' => 'HNTM CHINA TRUST',
                    'name' => 'CTBC',
                ],
                'HN SINOPAC'                                                  => [
                    'code' => '',
                    'name' => 'HN SINOPAC',
                ],
                'TAIPEI FUBONC.B'                                             => [
                    'code' => '',
                    'name' => 'TAIPEI FUBONC.B',
                ],
                'KIEN LONG (KIEN LONG BANK)'                                  => [
                    'code' => '',
                    'name' => 'KIEN LONG BANK',
                ],
                'PHAT TRIEN TP HCM (HD BANK)'                                 => [
                    'code' => 'HD BANK',
                    'name' => 'PHAT TRIEN TP HCM',
                ],
                'DAI CHUNG (PVCOMBANK)'                                       => [
                    'code' => 'PVCOMBANK',
                    'name' => 'DAI CHUNG',
                ],
                'BAC A (BAC A BANK)'                                          => [
                    'code' => 'BAC A BANK',
                    'name' => 'BAC A',
                ],
                'VIET A (VIET A BANK)'                                        => [
                    'code' => 'VIET A BANK',
                    'name' => 'VIET A',
                ],
                'PHAT TRIEN VIET NAM (VDB)'                                   => [
                    'code' => 'VDB',
                    'name' => 'PHAT TRIEN VIET NAM',
                ],
                'STANDARD CHARTERED BANK'                                     => [
                    'code' => '',
                    'name' => 'STANDARD CHARTERED BANK',
                ],
                'HONG LEONG VN'                                               => [
                    'code' => '',
                    'name' => 'HONG LEONG VN',
                ],
                'BNP-PARIBAS CN HCM'                                          => [
                    'code' => '',
                    'name' => 'BNP-PARIBAS CN HCM',
                ],
                'MIZUHO CORPORATE BANK, LTD'                                  => [
                    'code' => '',
                    'name' => 'MIZUHO CORPORATE BANK, LTD',
                ],
                'INDUSTRIAL AND COMMERCIAL BANK OF CHINA'                     => [
                    'code' => '',
                    'name' => 'INDUSTRIAL AND COMMERCIAL BANK OF CHINA',
                ],
                'QUOC TE (VIB)'                                               => [
                    'code' => 'VIB',
                    'name' => 'QUOC TE',
                ],
                'DAU TU VA PHAT TRIEN VN (BIDV)'                              => [
                    'code' => 'BIDV',
                    'name' => 'DAU TU VA PHAT TRIEN VN',
                ],
                'NHTNHH MTV ANZ (VN)'                                         => [
                    'code' => 'VN',
                    'name' => 'NHTNHH MTV ANZ',
                ],
                'MUFG CN HCM'                                                 => [
                    'code' => '',
                    'name' => 'MUFG CN HCM',
                ],
                'MEGA INTERNATIONAL COMMERCIAL BANK HCM'                      => [
                    'code' => '',
                    'name' => 'MEGA INTERNATIONAL COMMERCIAL BANK HCM',
                ],
                'FIRST COMMERCIALBANK - CN TP.HCM'                            => [
                    'code' => '',
                    'name' => 'FIRST COMMERCIALBANK - CN TP.HCM',
                ],
                'NH HOP TAC XA VN'                                            => [
                    'code' => '',
                    'name' => 'NH HOP TAC XA VN',
                ],
                'MUFG CN HN'                                                  => [
                    'code' => '',
                    'name' => 'MUFG CN HN',
                ],
                'NHTM TAIPEI FUBON - CN HO CHI MINH'                          => [
                    'code' => '',
                    'name' => 'NHTM TAIPEI FUBON - CN HO CHI MINH',
                ],
                'HN DTPT COMPUCHIA HA NOI'                                    => [
                    'code' => '',
                    'name' => 'HN DTPT COMPUCHIA HA NOI',
                ],
                'HN WOORI - CN TP.HCM'                                        => [
                    'code' => '',
                    'name' => 'HN WOORI - CN TP.HCM',
                ],
                'MALAYAN BANKING BERHAD - CNTPHCM'                            => [
                    'code' => '',
                    'name' => 'MALAYAN BANKING BERHAD - CNTPHCM',
                ],
                'SHANGHAI COMMERCIAL & SAVINGS BANK LTD - CHI NHANH DONG NAI' => [
                    'code' => '',
                    'name' => 'SHANGHAI COMMERCIAL & SAVINGS BANK LTD - CHI NHANH DONG NAI',
                ],
                'NH DAU TU VA PHAT TRIEN CAMPUCHIA, CN TP.HCM'                => [
                    'code' => '',
                    'name' => 'NH DAU TU VA PHAT TRIEN CAMPUCHIA, CN TP.HCM',
                ],
                'NH CREDIT AGRICOLE CIB CN TP.HCM'                            => [
                    'code' => '',
                    'name' => 'NH CREDIT AGRICOLE CIB CN TP.HCM',
                ],
                'ABC HN BRANCH'                                               => [
                    'code' => '',
                    'name' => 'ABC HN BRANCH',
                ],
                'NHTM TAIPEI FUBON BINH DUONG'                                => [
                    'code' => '',
                    'name' => 'NHTM TAIPEI FUBON BINH DUONG',
                ],
                'LIEN DOANH VIET NGA (VRB)'                                   => [
                    'code' => 'VRB',
                    'name' => 'LIEN DOANH VIET NGA',
                ],
                'VINASIAM BANK CN DONG NAI'                                   => [
                    'code' => '',
                    'name' => 'VINASIAM BANK CN DONG NAI',
                ],
                'KEB HANA CN HANOI'                                           => [
                    'code' => '',
                    'name' => 'KEB HANA CN HANOI',
                ],
                'NH KOOKMIN THANH PHO HO CHI MINH'                            => [
                    'code' => '',
                    'name' => 'NH KOOKMIN THANH PHO HO CHI MINH',
                ],
                'NH NONGHUYP CN HN'                                           => [
                    'code' => '',
                    'name' => 'NH NONGHUYP CN HN',
                ],
                'NGAN HANG KEB HANA CN TP.HCM'                                => [
                    'code' => '',
                    'name' => 'NGAN HANG KEB HANA CN TP.HCM',
                ],
                'HUA NAN COMMERCIAL BANK CO.,LTD, HCM'                        => [
                    'code' => '',
                    'name' => 'HUA NAN COMMERCIAL BANK CO.,LTD, HCM',
                ]
            ]
        ];
        return response()->json($data);
    }

}