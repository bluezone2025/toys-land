<?php

namespace Database\Seeders;

use App\Models\AgeGroup;
use App\Models\Category;
use App\Models\Country;
use App\Models\Coupon;
use App\Models\Faq;
use App\Models\HeroSlide;
use App\Models\Page;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * Initial store content taken from the approved design (v13).
 */
class StoreSeeder extends Seeder
{
    public function run(): void
    {
        Setting::put([
            'store_name_ar' => 'تويز لاند',
            'store_name_en' => 'Toys Land',
            'store_tagline_ar' => 'عالم الوناسة وألعاب الذكاء لعيالكم',
            'store_tagline_en' => 'World of Joy & Smart Toys for Kids',
            'announcement_ar' => 'توصيل مجاني للطلبات فوق 15 د.ك | وتغليف هدايا يشرح الصدر مع كود الخصم: TOYS10 (خصم 10%)',
            'announcement_en' => 'Free Delivery on orders over 15 KD | Free Luxury Gift Wrap + 10% OFF Code: TOYS10',
            'phone' => '+965 2222 5555',
            'whatsapp' => '96599999999',
            'admin_email' => env('ADMIN_EMAIL', 'admin@toysland.com'),
            'address_ar' => 'الكويت - الري - الأفنيوز مول',
            'address_en' => 'Kuwait - Al-Rai - The Avenues Mall',
            'hours_ar' => 'يومياً: 10 صباحاً - 11 مساءً',
            'hours_en' => 'Daily: 10:00 AM - 11:00 PM',
            'map_query' => 'The Avenues Kuwait',
            'map_note_ar' => 'حياكم بمعرضنا في الأفنيوز مول - المرحلة الرابعة - قسم الأطفال',
            'map_note_en' => 'Visit us at The Avenues Mall - Phase 4 - Kids Zone',
            'free_shipping_threshold' => '15',
            'promo_hint_code' => 'TOYS10',
            'default_locale' => 'ar',
            'welcome_enabled' => '1',
            'welcome_version' => '1',
            'welcome_video' => 'https://assets.mixkit.co/videos/preview/mixkit-children-playing-with-colorful-blocks-42924-large.mp4',
            'pay_knet_enabled' => '1',
            'pay_card_enabled' => '1',
            'pay_applepay_enabled' => '1',
            'pay_cod_enabled' => '1',
        ]);

        $countries = [
            ['KW', '🇰🇼', 'الكويت', 'Kuwait', 'د.ك', 'KD', 1.0, 1.0, 'نفس اليوم (3 - 6 ساعات)', 'Same Day (3 - 6 Hours)'],
            ['SA', '🇸🇦', 'السعودية', 'Saudi Arabia', 'ر.س', 'SAR', 12.20, 3.5, '2 إلى 4 أيام عمل', '2 - 4 Business Days'],
            ['AE', '🇦🇪', 'الإمارات', 'UAE', 'د.إ', 'AED', 11.95, 3.5, '2 إلى 4 أيام عمل', '2 - 4 Business Days'],
            ['QA', '🇶🇦', 'قطر', 'Qatar', 'ر.ق', 'QAR', 11.85, 3.5, '2 إلى 4 أيام عمل', '2 - 4 Business Days'],
            ['BH', '🇧🇭', 'البحرين', 'Bahrain', 'د.ب', 'BHD', 1.23, 3.0, '2 إلى 4 أيام عمل', '2 - 4 Business Days'],
            ['OM', '🇴🇲', 'عمان', 'Oman', 'ر.ع', 'OMR', 1.25, 3.5, '2 إلى 4 أيام عمل', '2 - 4 Business Days'],
        ];
        foreach ($countries as $i => [$code, $flag, $ar, $en, $sar, $sen, $rate, $fee, $dar, $den]) {
            Country::updateOrCreate(['code' => $code], [
                'flag' => $flag, 'dial_code' => ['KW' => '965', 'SA' => '966', 'AE' => '971', 'QA' => '974', 'BH' => '973', 'OM' => '968'][$code] ?? null, 'name_ar' => $ar, 'name_en' => $en, 'symbol_ar' => $sar, 'symbol_en' => $sen,
                'rate' => $rate, 'shipping_fee' => $fee, 'delivery_ar' => $dar, 'delivery_en' => $den, 'sort' => $i,
            ]);
        }

        $categories = [
            ['educational', 'ألعاب الذكاء والعلوم', 'STEM & Smart Toys', 'تجارب علمية وروبوتات ذكية', 'Science kits & smart robots', 'تطوير الذكاء', 'Brain Boost', 'photo-1532094349884-543bc11b234d', 'edu'],
            ['blocks', 'مكعبات وتركيب', 'Building Blocks', 'قلاع ومجسمات وهندسة', 'Castles, tracks & building', 'بناء وابتكار', 'Build & Create', 'photo-1587654780291-39c9404d746b', 'edu'],
            ['action', 'سيارات وأكشن', 'RC Cars & Action', 'سرعة وسيارات ريموت كنترول', 'High-speed RC & dinosaurs', 'سرعة وحماس', 'Speed & Action', 'photo-1594787318286-3d835c1d207f', 'fun'],
            ['dolls', 'دمى وعرائس', 'Dolls & Plushies', 'دباديب ناعمة وبيوت دمى فخمة', 'Plush teddy bears & dollhouses', 'ملمس ناعم', 'Soft & Cozy', 'photo-1559454403-b8fb88521f11', 'fun'],
            ['outdoor', 'حركية وخارجية', 'Outdoor & Active', 'سكوترات مضيئة وخيام لعب', 'Light-up scooters & play tents', 'حركة ووناسة', 'Active Play', 'photo-1566576912321-d58ddd7a6088', 'fun'],
        ];
        foreach ($categories as $i => [$slug, $tar, $ten, $dar, $den, $bar, $ben, $img, $group]) {
            Category::updateOrCreate(['slug' => $slug], [
                'title_ar' => $tar, 'title_en' => $ten, 'desc_ar' => $dar, 'desc_en' => $den, 'badge_ar' => $bar, 'badge_en' => $ben,
                'image' => $this->localImage("https://images.unsplash.com/{$img}?auto=format&fit=crop&w=600&q=80", 'categories'), 'home_group' => $group, 'sort' => $i,
            ]);
        }

        $ages = [
            ['1-3', 'من سنة لي 3 سنوات', '1 to 3 Years', 'ألعاب حسية وقطنية آمنة للصغار', 'Safe sensory & plush toys for toddlers', 'pink'],
            ['3-5', 'من 3 لي 5 سنوات', '3 to 5 Years', 'مكعبات وتركيب وتلوين', 'Building blocks, puzzles & coloring', 'amber'],
            ['6-8', 'من 6 لي 8 سنوات', '6 to 8 Years', 'سيارات ريموت وروبوتات', 'RC racing cars & smart robots', 'sky'],
            ['9+', '9 سنوات وفوق', '9+ Years', 'تجارب علمية وألعاب ذكاء', 'Science labs & brain-boosting games', 'purple'],
        ];
        foreach ($ages as $i => [$key, $tar, $ten, $dar, $den, $color]) {
            AgeGroup::updateOrCreate(['key' => $key], ['title_ar' => $tar, 'title_en' => $ten, 'desc_ar' => $dar, 'desc_en' => $den, 'color' => $color, 'sort' => $i]);
        }

        $options = [
            ['edition', 'الإصدار العادي', 'Standard Edition', null, 0],
            ['edition', 'إصدار مضيء', 'Light-Up Edition', null, 1.5],
            ['edition', 'حجم ملكي عود', 'Deluxe Royal Size', null, 3],
            ['addon', 'تغليف هدية كشخة', 'Luxury Gift Wrap', '🎁', 1],
            ['addon', 'بطاريات أصلية', 'Duracell Batteries', '🔋', 0.75],
            ['addon', 'كرت إهداء باسم الياهل', 'Custom Name Gift Card', '💌', 0.5],
            ['addon', 'جنطة حفظ اللعبة', 'Toy Storage Bag', '🎒', 1.25],
        ];

        $catIds = Category::pluck('id', 'slug');
        $ageIds = AgeGroup::pluck('id', 'key');
        $products = [
            ['slug' => 'smart-talking-space-robot', 'category' => 'educational', 'age' => '6-8', 'name_ar' => 'روبوت الفضاء الذكي اللي يسولف', 'name_en' => 'Smart Talking Space Robot', 'price' => 12.50, 'badge_ar' => 'الأكثر طلباً 🚀', 'badge_en' => 'Best Seller 🚀', 'image' => 'https://images.unsplash.com/photo-1546776310-eef45dd6d63c?auto=format&fit=crop&w=700&q=80', 'desc_ar' => 'روبوت تفاعلي عجيب يعلم اليهال البرمجة بطريقة سهلة، يتحرك بالصوت والإشارات وفيه ليتات ملونة تونس.', 'desc_en' => 'Interactive smart robot that teaches kids basic coding easily, responds to voice & gestures with colorful LED lights.'],
            ['slug' => 'giant-colorful-building-blocks-250-pcs', 'category' => 'blocks', 'age' => '3-5', 'name_ar' => 'قلعة المكعبات الملونة العودة (250 قطعة)', 'name_en' => 'Giant Colorful Building Blocks (250 Pcs)', 'price' => 9.75, 'badge_ar' => 'تنمية مهارات 🧩', 'badge_en' => 'Skill Builder 🧩', 'image' => 'https://images.unsplash.com/photo-1587654780291-39c9404d746b?auto=format&fit=crop&w=700&q=80', 'desc_ar' => 'تشكيلة مكعبات بناء آمنة وألوانها تشرح الصدر، تساعد الياهل يبتكر ويبني قلاع وتقوي عضلات إيده.', 'desc_en' => 'Safe, vibrant building blocks set that helps children innovate, build castles, and develop fine motor skills.'],
            ['slug' => 'turbo-rc-racing-off-road-car', 'category' => 'action', 'age' => '6-8', 'name_ar' => 'سيارة سباق تيربو بالريموت كنترول', 'name_en' => 'Turbo RC Racing Off-Road Car', 'price' => 14.00, 'badge_ar' => 'سرعة خيالية 🏎️', 'badge_en' => 'High Speed 🏎️', 'image' => 'https://images.unsplash.com/photo-1594787318286-3d835c1d207f?auto=format&fit=crop&w=700&q=80', 'desc_ar' => 'سيارة دفع رباعي طيارة مع ليتات LED وبطارية شحن تطول مع ولدك حق التحديات والسباقات.', 'desc_en' => 'Super-fast 4WD remote control car with LED headlights and long-lasting rechargeable battery for racing thrills.'],
            ['slug' => 'giant-soft-polar-teddy-bear', 'category' => 'dolls', 'age' => '1-3', 'name_ar' => 'الدب القطبي الناعم العود', 'name_en' => 'Giant Soft Polar Teddy Bear', 'price' => 6.50, 'badge_ar' => 'ناعم حيل 🧸', 'badge_en' => 'Ultra Soft 🧸', 'image' => 'https://images.unsplash.com/photo-1559454403-b8fb88521f11?auto=format&fit=crop&w=700&q=80', 'desc_ar' => 'دبدوب محشي بقطن طبيعي ما يسبب حساسية، يهبل حق اليهال الصغار يلمونه وينامون يمه بأمان.', 'desc_en' => 'Hypoallergenic natural cotton plush teddy bear, perfect for toddlers to hug and sleep beside safely.'],
            ['slug' => 'kids-fun-science-chemistry-lab-kit', 'category' => 'educational', 'age' => '9+', 'name_ar' => 'مختبر العلوم والتجارب العجيبة لليهال', 'name_en' => 'Kids Fun Science & Chemistry Lab Kit', 'price' => 11.25, 'badge_ar' => 'عالم صغير 🔬', 'badge_en' => 'Little Scientist 🔬', 'image' => 'https://images.unsplash.com/photo-1532094349884-543bc11b234d?auto=format&fit=crop&w=700&q=80', 'desc_ar' => 'أكثر من 30 تجربة علمية آمنة وتونس، تخلي ولدك أو بنتك يحبون العلوم والاكتشافات جنهم علماء صج.', 'desc_en' => 'Over 30 safe and exciting scientific experiments that spark curiosity and teach chemistry & physics fundamentals.'],
            ['slug' => 'classic-wooden-adventure-train-set', 'category' => 'blocks', 'age' => '3-5', 'name_ar' => 'قطار المغامرات الخشبي الكلاسيكي', 'name_en' => 'Classic Wooden Adventure Train Set', 'price' => 8.50, 'badge_ar' => 'خشب أصلي 🚂', 'badge_en' => 'Natural Wood 🚂', 'image' => 'https://images.unsplash.com/photo-1515488042361-ee00e0ddd4e4?auto=format&fit=crop&w=700&q=80', 'desc_ar' => 'سكة قطار خشبية متكاملة مع جسور وأشجار وعربات مغناطيس، تنمي خيال الياهل وتخليه يألف قصص.', 'desc_en' => 'Complete wooden railway track with bridges, trees, and magnetic carriages to inspire imaginative storytelling.'],
            ['slug' => '3-wheel-light-up-kids-scooter', 'category' => 'outdoor', 'age' => '3-5', 'name_ar' => 'سكوتر اليهال المضيء بو ثلاث تواير', 'name_en' => '3-Wheel Light-Up Kids Scooter', 'price' => 16.50, 'badge_ar' => 'حركة ونشاط 🛴', 'badge_en' => 'Active Play 🛴', 'image' => 'https://images.unsplash.com/photo-1566576912321-d58ddd7a6088?auto=format&fit=crop&w=700&q=80', 'desc_ar' => 'سكوتر متوازن وآمن حيل، توايره تشب ليتات لما يمشي، وتقدر تطول وتقصر السكان على طول الياهل.', 'desc_en' => 'Balanced and safe 3-wheel scooter with motion-activated glowing LED wheels and adjustable handlebar height.'],
            ['slug' => 'luxury-3-story-wooden-dollhouse', 'category' => 'dolls', 'age' => '6-8', 'name_ar' => 'بيت الدمى الخشبي الفخم مع الأثاث', 'name_en' => 'Luxury 3-Story Wooden Dollhouse', 'price' => 24.00, 'badge_ar' => 'هدية كشخة 🏰', 'badge_en' => 'Royal Gift 🏰', 'image' => 'https://images.unsplash.com/photo-1558060370-d644479cb6f7?auto=format&fit=crop&w=700&q=80', 'desc_ar' => 'بيت الأحلام من 3 طوابق مجهز بالكامل مع غرف وأثاث ملون وليتات داخلية، أحلى هدية تفرح البنوتات.', 'desc_en' => 'Dream 3-story wooden dollhouse fully furnished with miniature rooms, colorful furniture, and warm indoor lights.'],
            ['slug' => 'magic-magnetic-drawing-doodle-board', 'category' => 'educational', 'age' => '1-3', 'name_ar' => 'لوح الرسم والتلوين المغناطيسي السحري', 'name_en' => 'Magic Magnetic Drawing & Doodle Board', 'price' => 4.75, 'badge_ar' => 'فنان المستقبل 🎨', 'badge_en' => 'Future Artist 🎨', 'image' => 'https://images.unsplash.com/photo-1513364776144-60967b0f800f?auto=format&fit=crop&w=700&q=80', 'desc_ar' => 'لوح رسم نظيف وما يدمر الملابس ولا يحتاج حبر، معاه أختام أشكال وقلم مغناطيسي ينمسح بلمسة وحدة.', 'desc_en' => 'Mess-free magnetic drawing board with geometric stamps and an easy-slide eraser for endless creative doodling.'],
            ['slug' => 'walking-roaring-t-rex-dinosaur-toy', 'category' => 'action', 'age' => '6-8', 'name_ar' => 'ديناصور المغامرات المتحرك بالصوت والليتات', 'name_en' => 'Walking Roaring T-Rex Dinosaur Toy', 'price' => 7.90, 'badge_ar' => 'عالم الديناصورات 🦖', 'badge_en' => 'Jurassic World 🦖', 'image' => 'https://images.unsplash.com/photo-1560114928-40f1f1eb26a0?auto=format&fit=crop&w=700&q=80', 'desc_ar' => 'مجسم ديناصور يمشي ويطلع أصوات حماسية مع إضاءة، يخلي جو اللعب كله أكشن ومغامرات.', 'desc_en' => 'Interactive walking dinosaur figure with realistic roaring sounds and glowing LED eyes for action-packed play.'],
            ['slug' => 'colorful-modeling-clay-set-12-cans', 'category' => 'educational', 'age' => '3-5', 'name_ar' => 'طقم صلصال الأشكال الملونة (12 لون)', 'name_en' => 'Colorful Modeling Clay Set (12 Cans)', 'price' => 3.50, 'badge_ar' => 'آمن 100% 🌈', 'badge_en' => '100% Non-Toxic 🌈', 'image' => 'https://images.unsplash.com/photo-1596461404969-9ae70f2830c1?auto=format&fit=crop&w=700&q=80', 'desc_ar' => 'صلصال طبيعي طري ما يلزق بالإيد ولا يوصخ، معاه قوالب حيوانات ونجوم تقوي أصابع الياهل.', 'desc_en' => 'Soft, non-sticky natural modeling dough with animal & star molds to strengthen little hands and creativity.'],
            ['slug' => 'spacious-kids-indoor-play-tent-castle', 'category' => 'outdoor', 'age' => '3-5', 'name_ar' => 'خيمة اللعب المنزلية الوسيعة لليهال', 'name_en' => 'Spacious Kids Indoor Play Tent Castle', 'price' => 10.50, 'badge_ar' => 'زاوية الوناسة ⛺', 'badge_en' => 'Cozy Corner ⛺', 'image' => 'https://images.unsplash.com/photo-1526634332515-d56c5fd16991?auto=format&fit=crop&w=700&q=80', 'desc_ar' => 'خيمة لعب وسيعة تنصفط وتتركب بسهولة، معاها حبل ليتات دافي يسوي جو خيالي لعيالك بالبيت.', 'desc_en' => 'Easy-to-assemble foldable kids play tent complete with warm star string lights for a magical hideout.'],
        ];
        foreach ($products as $i => $p) {
            $product = Product::updateOrCreate(['slug' => $p['slug']], [
                'sku' => 'TL-'.str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
                'stock' => 50,
                'is_best_seller' => true,
                'sort' => $i,
                ...collect($p)->except(['slug', 'category', 'age', 'image'])->all(),
                'image' => $this->localImage($p['image'], 'products'),
            ]);
            $product->categories()->syncWithoutDetaching([$catIds[$p['category']]]);
            $product->ageGroups()->syncWithoutDetaching([$ageIds[$p['age']]]);
            if (! $product->options()->exists()) {
                foreach ($options as $j => [$type, $ar, $en, $icon, $price]) {
                    $product->options()->create(['type' => $type, 'name_ar' => $ar, 'name_en' => $en, 'icon' => $icon, 'price' => $price, 'sort' => $j]);
                }
            }
        }

        $slides = [
            ['عروضنا اليديدة لا تطوفكم 🧸', 'New Season Offers 🧸', 'ألعاب ذكية تفتح مخ عيالك وتنمي إبداعهم', "Smart Toys That Spark Your Child's Imagination", 'أكبر تشكيلة بالكويت من ألعاب التركيب والعلوم والروبوتات الآمنة 100% لكل الأعمار.', "Kuwait's largest collection of 100% safe building blocks, science kits, and interactive robots.", 'شوف كل الألعاب', 'Explore All Toys', '/products', 'photo-1587654780291-39c9404d746b', 'slate'],
            ['هدايا كشخة لليهال 🎁', 'Luxury Kids Gifts 🎁', 'تغليف هدايا يشرح الصدر وتوصيل سريع لي باب البيت', 'Premium Gift Wrapping & Fast Home Delivery', 'نقي هدية ولدك أو بنتك وإحنا نغلفها لك بأحلى شريطة ونحط كرت باسمه مجاناً.', "Pick your child's favorite gift and we will wrap it with a custom greeting card for free.", 'شوف الهدايا والدمى', 'Discover Dolls & Gifts', '/category/dolls', 'photo-1596461404969-9ae70f2830c1', 'sky'],
            ['قسم الحماس والأكشن 🏎️', 'Action & Thrills 🏎️', 'سيارات الريموت التيربو والسكوترات المضيئة', 'Turbo RC Racing Cars & Light-Up Scooters', 'خل عيالك يعيشون جو الحماس والتحدي مع أقوى تشكيلة سيارات وألعاب خارجية.', 'Let your kids experience non-stop excitement with our top-quality RC cars and outdoor gear.', 'تسوق ألعاب الأكشن', 'Shop Action Toys', '/category/action', 'photo-1594787318286-3d835c1d207f', 'indigo'],
        ];
        if (! HeroSlide::exists()) {
            foreach ($slides as $i => [$bar, $ben, $tar, $ten, $dar, $den, $btnar, $btnen, $link, $img, $theme]) {
                HeroSlide::create([
                    'badge_ar' => $bar, 'badge_en' => $ben, 'title_ar' => $tar, 'title_en' => $ten, 'desc_ar' => $dar, 'desc_en' => $den,
                    'btn_ar' => $btnar, 'btn_en' => $btnen, 'link' => $link, 'theme' => $theme, 'sort' => $i,
                    'image' => $this->localImage("https://images.unsplash.com/{$img}?auto=format&fit=crop&w=1800&q=80", 'slides'),
                ]);
            }
        }

        $faqs = [
            ['شكثر ياخذ وقت توصيل الطلبات داخل الكويت ولدول الخليج؟', 'How long does delivery take inside Kuwait and to GCC countries?', 'داخل الكويت نوصل طلبج في نفس اليوم (خلال 3 إلى 6 ساعات عمل). أما لدول الخليج (السعودية، الإمارات، قطر، البحرين، عمان) فيستغرق الشحن السريع من 2 إلى 4 أيام عمل عبر أرامكس و DHL.', 'Inside Kuwait, we offer same-day delivery (within 3 to 6 hours). For GCC countries, express shipping takes 2 to 4 business days.'],
            ['هل خدمة تغليف الهدايا متوفرة؟ وشلون أطلب كرت باسم الياهل؟', 'Is gift wrapping available? How can I add a custom name card?', 'أكيد! تقدرين تختارين خيار "تغليف هدية كشخة" و "كرت إهداء باسم الياهل" من صفحة تفاصيل اللعبة، وتكتبين العبارة اللي تبينها في خانة الملاحظات بصفحة الدفع.', 'Yes! You can select Luxury Gift Wrapping and Custom Name Card on the product page, and write your message in the checkout notes.'],
            ['شنو طرق الدفع المتوفرة بالموقع؟ وهل هي آمنة؟', 'What payment methods do you accept? Are they secure?', 'نوفر جميع طرق الدفع الآمنة والمشفرة عبر بوابة MyFatoorah المعتمدة: كي نت (KNET)، فيزا وماستركارد، أبل باي (Apple Pay)، بالإضافة إلى خدمة الدفع عند الاستلام.', 'We accept KNET, Visa, MasterCard, Apple Pay via certified MyFatoorah gateway, as well as Cash on Delivery.'],
            ['شنو سياسة الاستبدال والاسترجاع لو اللعبة ما ناسبت عمر ولدي؟', 'What is your exchange and return policy?', 'تقدرون تستبدلون أو ترجعون أي لعبة خلال 14 يوم من تاريخ الاستلام بشرط تكون بحالتها الأصلية وبكرتونها المغلف مع إبراز الفاتورة الإلكترونية.', 'You can exchange or return any item within 14 days of delivery, provided it is unused and in its original sealed packaging.'],
            ['هل الألعاب آمنة للأطفال الصغار وما تسبب حساسية؟', 'Are your toys safe and non-toxic for toddlers?', 'نعم 100%، جميع ألعابنا مختارة من ماركات عالمية مرخصة وخالية تماماً من المواد الضارة (BPA-Free)، والدمى القطنية مضادة للحساسية.', 'Yes, 100%! All our toys are certified non-toxic, BPA-free, and our plush toys are made from hypoallergenic cotton.'],
            ['شلون أقدر أستخدم كود الخصم (TOYS10)؟', 'How do I apply the promo code (TOYS10)?', 'في صفحة إتمام الطلب (Checkout)، راح تلاقين خانة كود الخصم، اكتبي فيها TOYS10 واضغطي تطبيق وراح ينخصم لج 10% فوراً من قيمة الفاتورة.', 'On the Checkout page, enter TOYS10 in the promo code box and click Apply to get an instant 10% discount.'],
        ];
        if (! Faq::exists()) {
            foreach ($faqs as $i => [$qar, $qen, $aar, $aen]) {
                Faq::create(['q_ar' => $qar, 'q_en' => $qen, 'a_ar' => $aar, 'a_en' => $aen, 'sort' => $i]);
            }
        }

        $this->seedPages();

        foreach (['TOYS10', 'KUWAIT10'] as $code) {
            Coupon::firstOrCreate(['code' => $code], ['type' => 'percent', 'value' => 10]);
        }
    }

    /**
     * Download a design image into the public disk so it can be managed from the admin panel.
     * Falls back to the remote URL when the server has no outbound access.
     */
    protected function localImage(string $url, string $dir): string
    {
        $path = $dir.'/'.md5($url).'.jpg';
        if (Storage::disk('public')->exists($path)) {
            return $path;
        }
        try {
            $response = Http::timeout(20)->get($url);
            if ($response->successful() && str_starts_with((string) $response->header('Content-Type'), 'image/')) {
                Storage::disk('public')->put($path, $response->body());

                return $path;
            }
        } catch (\Throwable) {
            // Keep the remote URL.
        }

        return $url;
    }

    protected function seedPages(): void
    {
        Page::firstOrCreate(['slug' => 'about'], [
            'badge_ar' => 'قصتنا ورؤيتنا بالكويت 🌟', 'badge_en' => 'Our Story & Vision 🌟',
            'title_ar' => 'تويز لاند.. نصنع السعادة ونبني عقول المستقبل', 'title_en' => 'Toys Land.. Crafting Joy & Building Smart Minds',
            'subtitle_ar' => 'منذ انطلاقتنا في الكويت، أخذنا على عاتقنا توفير ألعاب تجمع بين الوناسة، الأمان التام، وتنمية الذكاء والمهارات لكل طفل في الخليج.',
            'subtitle_en' => 'Founded in Kuwait, our mission is to provide 100% safe, educational, and joyful toys for children across the GCC.',
            'image' => $this->localImage('https://images.unsplash.com/photo-1515488042361-ee00e0ddd4e4?auto=format&fit=crop&w=1600&q=80', 'pages'),
            'stats' => [
                ['value' => '+25,000', 'color' => 'sky', 'label_ar' => 'عائلة سعيدة بالكويت والخليج', 'label_en' => 'Happy Families in GCC'],
                ['value' => '100%', 'color' => 'pink', 'label_ar' => 'ألعاب آمنة ومعتمدة عالمياً', 'label_en' => 'Certified Non-Toxic & Safe'],
                ['value' => '+1,200', 'color' => 'amber', 'label_ar' => 'لعبة ذكاء وترفيه متنوعة', 'label_en' => 'Educational & Fun Toys'],
                ['value' => '24/7', 'color' => 'emerald', 'label_ar' => 'دعم وتوصيل بنفس اليوم', 'label_en' => 'Same-Day Express Delivery'],
            ],
            'cards' => [
                ['icon' => 'fa-solid fa-shield-heart', 'color' => 'sky', 'title_ar' => 'الأمان والجودة أولاً', 'title_en' => 'Safety & Quality First', 'body_ar' => 'نختبر جميع الألعاب للتأكد من خلوها من أي مواد ضارة، ونختار الخامات الخشبية والقطنية والبلاستيكية المعتمدة.', 'body_en' => 'Every toy is rigorously tested to ensure it is 100% BPA-free, non-toxic, and safe for children of all ages.'],
                ['icon' => 'fa-solid fa-brain', 'color' => 'purple', 'title_ar' => 'تنمية الذكاء والإبداع (STEM)', 'title_en' => 'STEM & Skill Development', 'body_ar' => 'نركز على الألعاب التي تحفز التفكير المنطقي، التجارب العلمية، والمهارات الحركية بعيداً عن الشاشات.', 'body_en' => 'We curate toys that stimulate critical thinking, coding, engineering, and hands-on creativity.'],
                ['icon' => 'fa-solid fa-gift', 'color' => 'pink', 'title_ar' => 'تجربة إهداء استثنائية', 'title_en' => 'Luxury Gift Experience', 'body_ar' => 'نوفر خدمة تغليف الهدايا الفاخرة مع إرفاق بطاقات تهنئة مطبوعة باسم الطفل لتصل الهدية بأبهى صورة.', 'body_en' => 'Complimentary luxury gift wrapping and personalized greeting cards for every special occasion.'],
            ],
        ]);

        Page::firstOrCreate(['slug' => 'privacy'], [
            'badge_ar' => 'حماية البيانات 🔒', 'badge_en' => 'Data Protection 🔒',
            'title_ar' => 'سياسة الخصوصية والأمان', 'title_en' => 'Privacy & Safety Policy',
            'cards' => [
                ['icon' => 'fa-solid fa-user-shield', 'color' => 'sky', 'title_ar' => 'سرية بيانات العملاء', 'title_en' => 'Customer Confidentiality', 'body_ar' => 'نلتزم بالحفاظ التام على سرية اسم العميل، رقم النقال، البريد الإلكتروني، وعناوين التوصيل، ولا يتم مشاركتها مع أي طرف ثالث إلا لشركة التوصيل.', 'body_en' => 'We strictly protect your personal data, phone numbers, and delivery addresses and never share them with third parties.'],
                ['icon' => 'fa-solid fa-credit-card', 'color' => 'emerald', 'title_ar' => 'تشفير المدفوعات (MyFatoorah)', 'title_en' => 'Encrypted Payments', 'body_ar' => 'تتم جميع عمليات الدفع الإلكتروني (كي نت، فيزا، ماستركارد، أبل باي) عبر خوادم بوابة MyFatoorah المشفرة بمعيار SSL 256-Bit دون تخزين بيانات بطاقتك.', 'body_en' => 'All online payments are processed through PCI-DSS & SSL encrypted MyFatoorah servers.'],
            ],
        ]);

        Page::firstOrCreate(['slug' => 'terms'], [
            'badge_ar' => 'الضوابط القانونية 📜', 'badge_en' => 'Legal Terms 📜',
            'title_ar' => 'الشروط والأحكام', 'title_en' => 'Terms & Conditions',
            'subtitle_ar' => 'آخر تحديث: أكتوبر 2026 — متجر تويز لاند الكويت', 'subtitle_en' => 'Last Updated: October 2026 — Toys Land Kuwait',
            'cards' => [
                ['icon' => '', 'color' => 'sky', 'title_ar' => '1. جودة وأصالة المنتجات', 'title_en' => '1. Product Authenticity & Safety', 'body_ar' => 'جميع الألعاب المعروضة في متجر تويز لاند أصلية 100% ومطابقة لمعايير السلامة والأمان الخليجية والعالمية، ومكفولة ضد أي عيوب مصنعية.', 'body_en' => 'All toys sold on Toys Land are 100% authentic, safety-certified, and guaranteed against manufacturing defects.'],
                ['icon' => '', 'color' => 'sky', 'title_ar' => '2. سياسة الاستبدال والاسترجاع (14 يوم)', 'title_en' => '2. Exchange & Return Policy (14 Days)', 'body_ar' => 'يحق للعميل استبدال أو استرجاع المنتجات خلال 14 يوماً من تاريخ الفاتورة، بشرط بقاء اللعبة في غلافها الأصلي غير مفتوحة، مع إرفاق الفاتورة الإلكترونية.', 'body_en' => 'Customers may exchange or return items within 14 days of purchase, provided items remain unopened in their original packaging with the invoice.'],
                ['icon' => '', 'color' => 'sky', 'title_ar' => '3. الأسعار والعملات وكودات الخصم', 'title_en' => '3. Pricing, Currencies & Promo Codes', 'body_ar' => 'الأسعار الأساسية محددة بالدينار الكويتي مع إمكانية عرضها بعملات دول الخليج. كود الخصم TOYS10 يمنح خصماً مباشراً بنسبة 10% على سلة المشتريات.', 'body_en' => 'Base prices are in KWD with automatic GCC currency conversion. Promo code TOYS10 grants a 10% discount at checkout.'],
            ],
        ]);

        Page::firstOrCreate(['slug' => 'shipping'], [
            'badge_ar' => 'الشحن والتوصيل السريع 🚚', 'badge_en' => 'Express Logistics 🚚',
            'title_ar' => 'التوصيل السريع والدفع', 'title_en' => 'Fast Shipping & Payment',
            'body_ar' => '<p>💡 جميع الطلبات تخرج من مخازننا مغلفة بعناية فائقة في سيارات مكيفة ومجهزة للحفاظ على جودة الألعاب والهدايا.</p>',
            'body_en' => '<p>💡 All orders are carefully packed and dispatched via temperature-controlled vehicles.</p>',
        ]);
    }
}
