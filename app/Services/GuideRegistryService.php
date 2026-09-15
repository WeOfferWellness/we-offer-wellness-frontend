<?php

namespace App\Services;

use App\Models\OfferingV3;
use App\Models\Product;
use App\Models\GuidePage;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class GuideRegistryService
{
    private const PUBLISHED_AT = '2026-06-13T00:00:00+00:00';

    private const MODALITIES = [
        'reiki' => [
            'label' => 'Reiki',
            'route_modality' => 'reiki',
            'guide_slug_base' => 'reiki',
            'format' => 'therapies',
            'summary' => 'a gentle complementary wellbeing practice centred on light touch, rest and a calm treatment setting',
            'origin' => 'Reiki developed in Japan in the early 20th century and is now widely offered as a complementary wellbeing treatment focused on rest, presence and gentle energy work.',
            'session' => 'A Reiki session usually happens fully clothed on a treatment couch or chair. The practitioner may place their hands lightly on or just above the body while guiding the pace of the session quietly and gently.',
            'uses' => 'People often explore Reiki for relaxation, emotional balance, calmer evenings and a sense of being less wound up than when they walked in.',
            'beginner' => 'Yes. Reiki is usually beginner-friendly because it is quiet, non-strenuous and does not ask you to perform anything clever apart from turning up and breathing normally.',
            'chooser' => 'Look for clear practitioner profiles, honest explanations about what the session does and does not involve, and a style that feels grounded rather than grandiose.',
            'need_guides' => ['stress', 'anxiety', 'emotional-balance', 'relaxation'],
            'related_modalities' => ['reflexology', 'sound-healing', 'meditation'],
            'safety_tags' => ['general'],
            'session_noun' => 'session',
            'session_plural' => 'sessions',
            'expect_title' => 'What to Expect from a Reiki Session',
            'expect_slug' => 'what-to-expect-from-a-reiki-session',
        ],
        'reflexology' => [
            'label' => 'Reflexology',
            'route_modality' => 'reflexology',
            'guide_slug_base' => 'reflexology',
            'format' => 'therapies',
            'summary' => 'a complementary therapy that focuses on the feet, hands or ears using pressure techniques intended to encourage relaxation',
            'origin' => 'Modern reflexology is usually traced through a mix of zone theory and later complementary therapy practice, with different schools shaping the style used today.',
            'session' => 'A reflexology session usually focuses on the feet, although some practitioners also work with the hands or ears. You can expect targeted pressure, slower rhythms and time to settle afterwards.',
            'uses' => 'People often try reflexology for deep relaxation, stressy weeks, sleep support and a feeling that their nervous system needs a quieter afternoon.',
            'beginner' => 'Yes. Reflexology is commonly chosen by beginners who want a treatment that feels focused and hands-on without needing special clothing or prior experience.',
            'chooser' => 'A good reflexology practitioner should explain the session clearly, ask sensible questions about comfort and health, and avoid making sweeping promises.',
            'need_guides' => ['stress', 'sleep', 'pain-management', 'relaxation'],
            'related_modalities' => ['reiki', 'massage', 'aromatherapy'],
            'safety_tags' => ['general'],
            'session_noun' => 'session',
            'session_plural' => 'sessions',
            'expect_title' => 'What to Expect from Reflexology',
            'expect_slug' => 'what-to-expect-from-reflexology',
        ],
        'breathwork' => [
            'label' => 'Breathwork',
            'route_modality' => 'breathwork',
            'guide_slug_base' => 'breathwork',
            'format' => 'therapies',
            'summary' => 'guided breathing practice that can range from slow, settling patterns to more active techniques',
            'origin' => 'Breathwork draws on many traditions, from yogic breathing and mindfulness practice to modern facilitated wellbeing sessions.',
            'session' => 'A breathwork session may include posture guidance, paced breathing, short holds, spoken prompts and time at the end to land properly rather than rushing off immediately.',
            'uses' => 'People often explore breathwork for stress, sleep routines, emotional regulation, calmer focus and moments when their head feels busier than helpful.',
            'beginner' => 'It can be suitable for beginners when the practitioner explains the method well and offers gentler options. Breathwork is not one thing, so style and intensity matter.',
            'chooser' => 'Choose a facilitator who explains the technique plainly, checks suitability before starting and makes it clear when a slower, gentler approach is the better option.',
            'need_guides' => ['stress-anxiety', 'sleep', 'burnout', 'emotional-regulation'],
            'related_modalities' => ['meditation', 'reiki', '9d-breathwork'],
            'safety_tags' => ['general', 'breathwork'],
            'session_noun' => 'session',
            'session_plural' => 'sessions',
            'expect_title' => 'What to Expect from a Breathwork Session',
            'expect_slug' => 'what-to-expect-from-a-breathwork-session',
        ],
        '9d-breathwork' => [
            'label' => '9D Breathwork',
            'route_modality' => '9d-breathwork',
            'guide_slug_base' => '9d-breathwork',
            'format' => 'therapies',
            'summary' => 'a guided breathwork format that often combines coached breathing with immersive sound, spoken prompts and sensory layering',
            'origin' => '9D Breathwork is a modern guided format rather than a historical tradition, blending facilitated breathing with immersive audio design.',
            'session' => 'A 9D Breathwork session often uses headphones or a carefully designed soundscape, alongside structured breathing rounds, emotional prompts and a slower closing period.',
            'uses' => 'People often try 9D Breathwork for stress, burnout, emotional release and a feeling that they need a more immersive reset than a standard relaxation session.',
            'beginner' => 'Beginners can try 9D Breathwork, but it helps to know that the experience may feel more immersive and emotionally charged than a simple slow-breathing class.',
            'chooser' => 'Look for a facilitator who explains the pace, the emotional intensity, and the suitability checks before pressing play and hoping for the best.',
            'need_guides' => ['stress-anxiety', 'burnout', 'emotional-release'],
            'related_modalities' => ['breathwork', 'sound-healing', 'meditation'],
            'safety_tags' => ['general', 'breathwork'],
            'session_noun' => 'session',
            'session_plural' => 'sessions',
            'expect_title' => 'What to Expect from a 9D Breathwork Session',
            'expect_slug' => 'what-to-expect-from-a-9d-breathwork-session',
        ],
        'massage-therapy' => [
            'label' => 'Massage Therapy',
            'route_modality' => 'massage',
            'guide_slug_base' => 'massage-therapy',
            'format' => 'therapies',
            'summary' => 'hands-on bodywork that may use pressure, movement and targeted techniques to support relaxation and body awareness',
            'origin' => 'Massage sits within many traditions and training styles, from relaxing full-body treatments to more targeted therapeutic approaches.',
            'session' => 'A massage therapy session usually begins with a short consultation, followed by hands-on work tailored to the area and pressure level you have agreed together.',
            'uses' => 'People often book massage for muscular tension, back or shoulder discomfort, stressy weeks, recovery time and the simple joy of unclenching a jaw they forgot they owned.',
            'beginner' => 'Yes. Massage is one of the most familiar entry points into complementary wellbeing care, especially when expectations and comfort levels are discussed properly.',
            'chooser' => 'Choose a therapist who asks clear health questions, explains pressure and positioning, and adapts the treatment rather than forcing one standard routine onto every body.',
            'need_guides' => ['back-pain', 'neck-and-shoulder-tension', 'stress', 'relaxation'],
            'related_modalities' => ['reflexology', 'acupuncture', 'reiki'],
            'safety_tags' => ['general', 'pain'],
            'session_noun' => 'session',
            'session_plural' => 'sessions',
            'expect_title' => 'What to Expect from a Massage Therapy Session',
            'expect_slug' => 'what-to-expect-from-a-massage-therapy-session',
        ],
        'meditation' => [
            'label' => 'Meditation',
            'route_modality' => 'meditation',
            'guide_slug_base' => 'meditation',
            'format' => 'therapies',
            'summary' => 'a guided or self-led awareness practice that usually works with attention, breath, sound or observation',
            'origin' => 'Meditation has roots across many contemplative traditions and is now taught in everything from wellbeing studios to online sessions and community classes.',
            'session' => 'A meditation session may include breath awareness, body scans, visualisation, mantra, silence or guided prompts. Some are quietly spacious; others are brisker and more structured.',
            'uses' => 'People often explore meditation for stress, sleep, calmer focus, nervous-system support and creating a more intentional pause in the day.',
            'beginner' => 'Yes, although beginners often benefit from guided sessions first. Contrary to popular myth, you do not need to achieve instant mental silence to count as doing it properly.',
            'chooser' => 'Look for a teacher or guide whose style matches what you need: soothing, practical, spiritually framed, secular, movement-based or sound-led.',
            'need_guides' => ['stress', 'sleep', 'anxiety', 'focus'],
            'related_modalities' => ['breathwork', 'yoga', 'sound-healing'],
            'safety_tags' => ['general'],
            'session_noun' => 'session',
            'session_plural' => 'sessions',
        ],
        'yoga' => [
            'label' => 'Yoga',
            'route_modality' => 'yoga',
            'guide_slug_base' => 'yoga',
            'format' => 'classes',
            'summary' => 'a movement and awareness practice that may blend postures, breath, mobility, strength and rest',
            'origin' => 'Yoga has deep roots in Indian philosophy and practice, with modern classes ranging from restorative and slow to strong and sweaty.',
            'session' => 'A yoga class may include breath-led movement, holds, mobility, balance work and relaxation at the end. Different styles can feel very different, which is half the point and half the confusion.',
            'uses' => 'People often use yoga for mobility, back support, stress management, sleep routines, body awareness and general wellbeing.',
            'beginner' => 'Yes, when the class level is clear and the teacher offers options. Beginner-friendly yoga should not feel like an audition for a bendier life.',
            'chooser' => 'Look for honest class descriptions, level guidance, a teacher who offers modifications and a pace that suits what your body can actually do right now.',
            'need_guides' => ['back-pain', 'stress', 'sleep', 'anxiety'],
            'related_modalities' => ['pilates', 'meditation', 'massage-therapy'],
            'safety_tags' => ['general', 'movement', 'pain'],
            'session_noun' => 'class',
            'session_plural' => 'classes',
            'expect_title' => 'What to Expect from a Yoga Class',
            'expect_slug' => 'what-to-expect-from-a-yoga-class',
        ],
        'sound-healing' => [
            'label' => 'Sound Healing',
            'route_modality' => 'sound-healing',
            'guide_slug_base' => 'sound-healing',
            'format' => 'therapies',
            'summary' => 'a sound-led wellbeing practice that may use singing bowls, gongs, voice or other instruments to support rest',
            'origin' => 'Sound healing, as offered in today’s wellbeing spaces, combines different traditions and contemporary facilitation styles around the use of sound and vibration.',
            'session' => 'A sound healing session often involves lying down while the practitioner works with bowls, gongs, chimes or voice. Some sessions are subtle and floaty; others have more volume and resonance.',
            'uses' => 'People often try sound healing for relaxation, sleep support, emotional decompression and a gentler route into stillness when silent meditation feels like a stretch.',
            'beginner' => 'Yes. Sound healing is often approachable for beginners because you mostly need a comfortable place to settle and a tolerance for being still with sound.',
            'chooser' => 'Choose a practitioner who explains instrument volume, session length and what the space feels like, especially if you are sensitive to noise or stimulation.',
            'need_guides' => ['stress', 'sleep', 'relaxation'],
            'related_modalities' => ['gong-bath', 'meditation', 'reiki'],
            'safety_tags' => ['general'],
            'session_noun' => 'session',
            'session_plural' => 'sessions',
            'expect_title' => 'What to Expect from a Sound Healing Session',
            'expect_slug' => 'what-to-expect-from-a-sound-healing-session',
        ],
        'gong-bath' => [
            'label' => 'Gong Bath',
            'route_modality' => 'gong-sound-bath',
            'guide_slug_base' => 'gong-bath',
            'format' => 'events',
            'summary' => 'an immersive sound experience built around gongs and resonant instruments, usually taken lying down',
            'origin' => 'Gong baths are a modern wellbeing format built around extended immersive sound, often hosted as scheduled events or group sessions.',
            'session' => 'A gong bath usually invites you to lie down with blankets and an eye pillow while the facilitator plays gongs and other instruments through a longer sound journey.',
            'uses' => 'People often book gong baths for deep relaxation, sleep support, decompression and those days when talking less and listening more sounds like very good planning.',
            'beginner' => 'Yes, as long as you are comfortable resting in a group setting and know the sound level may rise during parts of the session.',
            'chooser' => 'Check whether the event is quiet and meditative or more immersive and powerful, and whether the facilitator gives enough information about comfort, volume and timings.',
            'need_guides' => ['stress', 'sleep', 'relaxation'],
            'related_modalities' => ['sound-healing', 'meditation', 'reiki'],
            'safety_tags' => ['general'],
            'session_noun' => 'event',
            'session_plural' => 'events',
            'expect_title' => 'What to Expect from a Gong Bath',
            'expect_slug' => 'what-to-expect-from-a-gong-bath',
        ],
        'acupuncture' => [
            'label' => 'Acupuncture',
            'route_modality' => 'acupuncture',
            'guide_slug_base' => 'acupuncture',
            'format' => 'therapies',
            'summary' => 'a traditional East Asian medicine practice that uses very fine needles at selected points on the body',
            'origin' => 'Acupuncture comes from traditional Chinese medicine and related East Asian medical traditions, and is now offered across a wide range of modern clinics and wellbeing settings.',
            'session' => 'An acupuncture appointment usually includes a consultation, a treatment plan and the insertion of fine needles at chosen points. Some sessions include rest time after placement.',
            'uses' => 'People often explore acupuncture for pain management, stress support, relaxation and broader wellbeing care alongside conventional treatment.',
            'beginner' => 'It can suit beginners, especially when the practitioner explains the process clearly and checks how you feel about needles before starting.',
            'chooser' => 'Choose a qualified practitioner who explains training, hygiene, session planning and suitability clearly. Needle confidence should not be assumed just because you turned up.',
            'need_guides' => ['stress', 'pain-management', 'relaxation'],
            'related_modalities' => ['massage-therapy', 'reflexology', 'reiki'],
            'safety_tags' => ['general', 'pain'],
            'session_noun' => 'session',
            'session_plural' => 'sessions',
            'expect_title' => 'What to Expect from Acupuncture',
            'expect_slug' => 'what-to-expect-from-acupuncture',
        ],
        'aromatherapy' => [
            'label' => 'Aromatherapy',
            'route_modality' => 'aromatherapy',
            'guide_slug_base' => 'aromatherapy',
            'format' => 'therapies',
            'summary' => 'a complementary wellbeing practice that uses essential oils within massage, inhalation or room-based treatments',
            'origin' => 'Aromatherapy developed as a modern complementary therapy using essential oils and is often combined with massage or relaxation treatments.',
            'session' => 'An aromatherapy session may include consultation, oil selection and either inhalation-based relaxation or a treatment such as massage using diluted oils.',
            'uses' => 'People often try aromatherapy for stress, relaxation, sleep routines and sensory comfort, especially when scent helps them settle more quickly than stern self-talk does.',
            'beginner' => 'Yes, although scent preferences and sensitivities matter. A good aromatherapy session should feel tailored rather than like being marched through a perfume counter.',
            'chooser' => 'Look for a practitioner who asks about allergies, sensitivities, pregnancy and medical considerations before choosing oils.',
            'need_guides' => ['stress', 'sleep', 'relaxation'],
            'related_modalities' => ['massage-therapy', 'reflexology', 'reiki'],
            'safety_tags' => ['general'],
            'session_noun' => 'session',
            'session_plural' => 'sessions',
        ],
        'hypnotherapy' => [
            'label' => 'Hypnotherapy',
            'route_modality' => 'hypnotherapy',
            'guide_slug_base' => 'hypnotherapy',
            'format' => 'therapies',
            'summary' => 'a talking and guided relaxation approach that works with focused attention, suggestion and reflective change work',
            'origin' => 'Hypnotherapy combines guided relaxation, focused attention and suggestion-based techniques within a structured therapeutic conversation.',
            'session' => 'A hypnotherapy session often starts with conversation, goal setting and context, followed by guided relaxation or focused imagery and then a slower return to normal alertness.',
            'uses' => 'People often explore hypnotherapy for anxiety, stress, confidence, habits and mindset support as part of a broader wellbeing or therapeutic plan.',
            'beginner' => 'Yes, when expectations are realistic. You do not need to lose control, cluck like a hen or experience stage-show nonsense for hypnotherapy to be genuine.',
            'chooser' => 'Choose a practitioner who explains their process clearly, sets boundaries well and is careful about what hypnotherapy may support versus what needs clinical care.',
            'need_guides' => ['anxiety', 'confidence', 'stress'],
            'related_modalities' => ['life-coaching', 'meditation', 'breathwork'],
            'safety_tags' => ['general'],
            'session_noun' => 'session',
            'session_plural' => 'sessions',
        ],
        'energy-healing' => [
            'label' => 'Energy Healing',
            'route_modality' => 'energy-healing',
            'guide_slug_base' => 'energy-healing',
            'format' => 'therapies',
            'summary' => 'an umbrella term for complementary practices that work gently with rest, attention, touch and energetic framing',
            'origin' => 'Energy healing is a broad modern umbrella covering several complementary modalities, often shaped by the practitioner’s lineage, training and preferred style.',
            'session' => 'An energy healing session may involve light touch, hands hovering above the body, guided relaxation, visualisation or intuitive prompts depending on the practitioner’s method.',
            'uses' => 'People often explore energy healing for relaxation, grounding, emotional steadiness and making room for a calmer state after a loud week.',
            'beginner' => 'It can suit beginners, especially when the practitioner keeps the explanation practical and leaves space for personal interpretation.',
            'chooser' => 'Look for someone who explains their training, the structure of the session and what you can realistically expect to feel or not feel.',
            'need_guides' => [],
            'related_modalities' => ['reiki', 'crystal-healing', 'sound-healing'],
            'safety_tags' => ['general'],
            'session_noun' => 'session',
            'session_plural' => 'sessions',
        ],
        'life-coaching' => [
            'label' => 'Life Coaching',
            'route_modality' => 'life-coaching',
            'guide_slug_base' => 'life-coaching',
            'format' => 'therapies',
            'summary' => 'a structured conversation-based practice focused on goals, behaviour, reflection and practical momentum',
            'origin' => 'Life coaching grew from performance, leadership and personal development contexts, and is now used across wellbeing, career and confidence support.',
            'session' => 'A life coaching session is usually conversational and goal-focused. You may work on clarity, habits, motivation, decision-making or the next sensible step instead of circling the same thought for another fortnight.',
            'uses' => 'People often seek life coaching for confidence, motivation, burnout recovery, direction and accountability.',
            'beginner' => 'Yes, particularly for people who want practical structure and reflection rather than a hands-on or body-based session.',
            'chooser' => 'Choose a coach who is clear about scope, boundaries, outcomes and what coaching is not. It should feel practical, not theatrical.',
            'need_guides' => ['confidence', 'motivation', 'burnout'],
            'related_modalities' => ['hypnotherapy', 'meditation', 'breathwork-coaching'],
            'safety_tags' => ['general'],
            'session_noun' => 'session',
            'session_plural' => 'sessions',
        ],
        'pilates' => [
            'label' => 'Pilates',
            'route_modality' => 'pilates',
            'guide_slug_base' => 'pilates',
            'format' => 'classes',
            'summary' => 'a movement practice focused on control, alignment, strength, breath and body awareness',
            'origin' => 'Pilates was developed by Joseph Pilates and has evolved into a broad movement practice taught on mats and specialist equipment.',
            'session' => 'A Pilates class usually centres on controlled movement, core engagement, posture, mobility and breath. The pace may be calm, but your muscles often notice the memo later.',
            'uses' => 'People often try Pilates for posture, back support, core strength, stability and feeling more connected to how they move day to day.',
            'beginner' => 'Yes, when the class level is clear and the instructor offers modifications for injuries, mobility limits and confidence levels.',
            'chooser' => 'Look for level guidance, safety cues, adaptation options and an instructor who can explain the purpose of the movement rather than simply counting to eight with determination.',
            'need_guides' => ['back-pain', 'posture', 'core-strength'],
            'related_modalities' => ['yoga', 'massage-therapy', 'meditation'],
            'safety_tags' => ['general', 'movement', 'pain'],
            'session_noun' => 'class',
            'session_plural' => 'classes',
        ],
        'nutrition-coaching' => [
            'label' => 'Nutrition Coaching',
            'route_modality' => 'nutrition',
            'guide_slug_base' => 'nutrition-coaching',
            'format' => 'therapies',
            'summary' => 'practical one-to-one support around food habits, routines, goals and sustainable behaviour change',
            'origin' => 'Nutrition coaching is a modern support format focused on practical habits, education and consistency rather than one dramatic shopping list and a lecture.',
            'session' => 'A nutrition coaching session usually involves conversation, goal review, routine planning and realistic adjustments around meals, hydration, shopping and consistency.',
            'uses' => 'People often explore nutrition coaching for energy, routine, confidence around food choices and wellbeing habits that need to work in normal life, not fantasy life.',
            'beginner' => 'Yes, especially for people who want guided practical support rather than trying to decode conflicting food advice alone.',
            'chooser' => 'Look for a coach who is clear about qualifications, scope and whether they provide coaching, nutrition education or registered dietetic support.',
            'need_guides' => [],
            'related_modalities' => ['life-coaching', 'ayurveda', 'pilates'],
            'safety_tags' => ['general'],
            'session_noun' => 'session',
            'session_plural' => 'sessions',
        ],
        'eft-tapping' => [
            'label' => 'EFT Tapping',
            'route_modality' => 'eft',
            'guide_slug_base' => 'eft-tapping',
            'format' => 'therapies',
            'summary' => 'a guided self-tapping practice that combines acupressure points with spoken prompts and reflection',
            'origin' => 'EFT, often called tapping, is a modern guided practice that combines tapping on selected points with spoken phrases and self-reflection.',
            'session' => 'An EFT session usually involves identifying the issue you want to work with, tapping on a sequence of points and using spoken prompts to help regulate the experience.',
            'uses' => 'People often try EFT Tapping for stress, emotional steadiness, overwhelm and creating a practical self-soothing tool they can use outside the session.',
            'beginner' => 'Yes. Many beginners like EFT because it is guided, practical and easy to repeat at home once the basics make sense.',
            'chooser' => 'Look for an EFT practitioner who explains the method clearly and works at a pace that feels safe rather than needlessly intense.',
            'need_guides' => [],
            'related_modalities' => ['hypnotherapy', 'breathwork', 'meditation'],
            'safety_tags' => ['general'],
            'session_noun' => 'session',
            'session_plural' => 'sessions',
        ],
        'crystal-healing' => [
            'label' => 'Crystal Healing',
            'route_modality' => 'crystal-healing',
            'guide_slug_base' => 'crystal-healing',
            'format' => 'therapies',
            'summary' => 'a complementary wellbeing practice that uses crystals within rest-based, energy-led or ritual-style sessions',
            'origin' => 'Crystal healing is a contemporary complementary practice shaped by different spiritual and energetic traditions rather than one single formal system.',
            'session' => 'A crystal healing session may include rest, light touch, crystals placed on or around the body, visualisation and grounding time at the end.',
            'uses' => 'People often explore crystal healing for relaxation, reflection, emotional grounding and a more ritual-feeling wellbeing session.',
            'beginner' => 'Yes, provided the practitioner explains the approach plainly and does not assume you already speak fluent crystal.',
            'chooser' => 'Choose someone who is transparent about their style, keeps claims measured and makes the experience feel welcoming rather than cryptic.',
            'need_guides' => [],
            'related_modalities' => ['energy-healing', 'reiki', 'sound-healing'],
            'safety_tags' => ['general'],
            'session_noun' => 'session',
            'session_plural' => 'sessions',
        ],
        'ayurveda' => [
            'label' => 'Ayurveda',
            'route_modality' => 'ayurveda',
            'guide_slug_base' => 'ayurveda',
            'format' => 'therapies',
            'summary' => 'a traditional Indian system of wellbeing that may include lifestyle guidance, bodywork and personalised routines',
            'origin' => 'Ayurveda is a traditional Indian system with a long history, and modern offerings may include consultation, lifestyle support, body treatments and routine guidance.',
            'session' => 'An Ayurveda session can vary widely. Some focus on consultation and lifestyle advice, while others include treatments, oils, massage or seasonal routine support.',
            'uses' => 'People often explore Ayurveda for balance, routine, digestive wellbeing, stress support and a more personalised approach to daily habits.',
            'beginner' => 'Yes, especially when the practitioner explains concepts in plain language and shows how recommendations fit real life rather than an idealised wellness spreadsheet.',
            'chooser' => 'Look for a practitioner who can explain their training, their approach and what parts of Ayurveda they actually offer in practice.',
            'need_guides' => [],
            'related_modalities' => ['nutrition-coaching', 'massage-therapy', 'meditation'],
            'safety_tags' => ['general'],
            'session_noun' => 'session',
            'session_plural' => 'sessions',
        ],
        'breathwork-coaching' => [
            'label' => 'Breathwork Coaching',
            'route_modality' => 'breathwork',
            'guide_slug_base' => 'breathwork-coaching',
            'format' => 'therapies',
            'summary' => 'guided one-to-one breathwork support focused on practice selection, technique and integrating breathing tools into daily life',
            'origin' => 'Breathwork coaching is a modern one-to-one support format that blends guided breathing with education, habit-building and tailored practice selection.',
            'session' => 'A breathwork coaching session often combines explanation, live guided practice and practical planning around when and how to use certain breathing tools.',
            'uses' => 'People often explore breathwork coaching for stress support, steadier routines, confidence with technique and help choosing what kind of breath practice suits them.',
            'beginner' => 'Yes, and coaching can be especially helpful for beginners who want a tailored starting point rather than a one-size-fits-all class.',
            'chooser' => 'Look for a coach who explains the method clearly, checks health considerations and can adapt practices to your goals and comfort level.',
            'need_guides' => [],
            'related_modalities' => ['breathwork', 'meditation', 'life-coaching'],
            'safety_tags' => ['general', 'breathwork'],
            'session_noun' => 'session',
            'session_plural' => 'sessions',
        ],
    ];

    private const NEEDS = [
        'back-pain' => [
            'title' => 'Back Pain',
            'slug' => 'back-pain',
            'need_slug' => 'pain-management',
            'keywords' => ['back pain', 'pain', 'mobility', 'support'],
            'problem' => 'Back pain can make ordinary tasks feel oddly strategic, from tying laces to deciding whether that chair is friend or foe.',
            'support' => 'People often look for complementary practices that may ease tension, improve body awareness or support a steadier recovery plan.',
            'medical' => 'If your pain is severe, sudden, worsening, linked to injury, or comes with numbness, weakness or other concerning symptoms, seek medical advice before starting a new movement or complementary wellbeing practice.',
            'related_modalities' => ['massage-therapy', 'pilates', 'acupuncture'],
        ],
        'stress' => [
            'title' => 'Stress',
            'slug' => 'stress',
            'need_slug' => 'stress-and-anxiety',
            'keywords' => ['stress', 'relax', 'calm', 'reset'],
            'problem' => 'Stress has a habit of showing up everywhere at once: in your shoulders, your sleep and the tone you use with the toaster.',
            'support' => 'People often explore supportive wellbeing practices to create calmer routines, clearer breathing patterns and a more settled nervous system.',
            'medical' => '',
            'related_modalities' => ['meditation', 'breathwork', 'reiki'],
        ],
        'sleep' => [
            'title' => 'Sleep',
            'slug' => 'sleep',
            'need_slug' => 'sleep-issues',
            'keywords' => ['sleep', 'rest', 'night', 'settle'],
            'problem' => 'When sleep is patchy, everything else usually feels louder, slower or more dramatic than it needs to.',
            'support' => 'People often look for calming practices that may help them wind down, feel more grounded and create gentler evening habits.',
            'medical' => '',
            'related_modalities' => ['meditation', 'sound-healing', 'reflexology'],
        ],
        'anxiety' => [
            'title' => 'Anxiety',
            'slug' => 'anxiety',
            'need_slug' => 'stress-and-anxiety',
            'keywords' => ['anxiety', 'calm', 'grounding', 'support'],
            'problem' => 'Anxiety can feel fast, loud and physically tiring, even when you are technically sitting still doing nothing suspicious.',
            'support' => 'People may explore complementary wellbeing sessions that support grounding, relaxation and steadier body awareness alongside professional care where needed.',
            'medical' => '',
            'related_modalities' => ['hypnotherapy', 'meditation', 'reiki'],
        ],
        'stress-anxiety' => [
            'title' => 'Stress and Anxiety',
            'slug' => 'stress-anxiety',
            'need_slug' => 'stress-and-anxiety',
            'keywords' => ['stress', 'anxiety', 'calm', 'grounding'],
            'problem' => 'When stress and anxiety overlap, it can feel like body tension and mental noise are running a small but very committed partnership.',
            'support' => 'People often explore calming complementary practices to support relaxation, steadier breathing and a more grounded sense of control.',
            'medical' => '',
            'related_modalities' => ['reiki', 'meditation', 'reflexology'],
        ],
        'burnout' => [
            'title' => 'Burnout',
            'slug' => 'burnout',
            'need_slug' => 'low-mood-burnout',
            'keywords' => ['burnout', 'overwhelm', 'rest', 'reset'],
            'problem' => 'Burnout can leave you feeling flat, foggy and oddly annoyed by tasks you used to handle without a second thought.',
            'support' => 'People may look for practices that encourage rest, emotional decompression and a slower route back to feeling more like themselves.',
            'medical' => '',
            'related_modalities' => ['breathwork', '9d-breathwork', 'life-coaching'],
        ],
        'emotional-regulation' => [
            'title' => 'Emotional Regulation',
            'slug' => 'emotional-regulation',
            'need_slug' => 'nervous-system',
            'keywords' => ['emotional regulation', 'nervous system', 'grounding', 'support'],
            'problem' => 'If your emotions feel close to the surface or hard to settle, a structured calming practice can sometimes help create more space before reaction.',
            'support' => 'People often explore body-based or breath-led wellbeing practices to feel more grounded, less reactive and more connected to what they need.',
            'medical' => '',
            'related_modalities' => ['breathwork', 'meditation', 'reiki'],
        ],
        'emotional-release' => [
            'title' => 'Emotional Release',
            'slug' => 'emotional-release',
            'need_slug' => 'nervous-system',
            'keywords' => ['emotional release', 'release', 'nervous system', 'support'],
            'problem' => 'Sometimes people are not looking to solve every feeling neatly; they simply want a safe way to exhale some of what has built up.',
            'support' => 'Immersive and body-led practices may help some people process tension, soften emotional holding and feel more grounded afterwards.',
            'medical' => '',
            'related_modalities' => ['9d-breathwork', 'breathwork', 'sound-healing'],
        ],
        'pain-management' => [
            'title' => 'Pain Management',
            'slug' => 'pain-management',
            'need_slug' => 'pain-management',
            'keywords' => ['pain', 'support', 'mobility', 'tension'],
            'problem' => 'When pain becomes part of the background, even small plans can require more negotiation than anyone asked for.',
            'support' => 'People often explore supportive practices that may help with relaxation, body awareness and managing muscular tension alongside medical advice.',
            'medical' => 'If your pain is severe, sudden, worsening, linked to injury, or comes with numbness, weakness or other concerning symptoms, seek medical advice before starting a new movement or complementary wellbeing practice.',
            'related_modalities' => ['acupuncture', 'massage-therapy', 'reflexology'],
        ],
        'relaxation' => [
            'title' => 'Relaxation',
            'slug' => 'relaxation',
            'need_slug' => 'nervous-system',
            'keywords' => ['relaxation', 'calm', 'rest', 'settle'],
            'problem' => 'Sometimes the goal is not performance, insight or peak anything. It is simply relaxing properly for once.',
            'support' => 'People often book complementary wellbeing sessions to switch pace, settle the body and make space for calm.',
            'medical' => '',
            'related_modalities' => ['sound-healing', 'reiki', 'massage-therapy'],
        ],
        'emotional-balance' => [
            'title' => 'Emotional Balance',
            'slug' => 'emotional-balance',
            'need_slug' => 'nervous-system',
            'keywords' => ['emotional balance', 'grounding', 'calm', 'support'],
            'problem' => 'When you feel emotionally stretched, steadier routines and supportive sessions can help create a sense of balance again.',
            'support' => 'People may explore gentle complementary practices that support grounding, reflection and emotional calm.',
            'medical' => '',
            'related_modalities' => ['reiki', 'meditation', 'sound-healing'],
        ],
        'neck-and-shoulder-tension' => [
            'title' => 'Neck and Shoulder Tension',
            'slug' => 'neck-and-shoulder-tension',
            'need_slug' => 'pain-management',
            'keywords' => ['neck', 'shoulder', 'tension', 'pain'],
            'problem' => 'Neck and shoulder tension is one of those problems that can make both desk work and sleep feel far more dramatic than they should.',
            'support' => 'People often look for complementary support that may ease muscular tightness, encourage body awareness and help them move more comfortably.',
            'medical' => 'If your pain is severe, sudden, worsening, linked to injury, or comes with numbness, weakness or other concerning symptoms, seek medical advice before starting a new movement or complementary wellbeing practice.',
            'related_modalities' => ['massage-therapy', 'acupuncture', 'yoga'],
        ],
        'focus' => [
            'title' => 'Focus',
            'slug' => 'focus',
            'need_slug' => 'overwhelm',
            'keywords' => ['focus', 'clarity', 'attention', 'calm'],
            'problem' => 'If your attention feels scattered, even a short pause can help stop the day from turning into one long tab-switch.',
            'support' => 'People often explore guided practices that may support attention, calmer breathing and clearer mental space.',
            'medical' => '',
            'related_modalities' => ['meditation', 'breathwork', 'life-coaching'],
        ],
        'confidence' => [
            'title' => 'Confidence',
            'slug' => 'confidence',
            'need_slug' => 'worry',
            'keywords' => ['confidence', 'self-belief', 'mindset', 'support'],
            'problem' => 'Confidence struggles can show up quietly in decision-making, boundaries and how much second-guessing fits into a normal Tuesday.',
            'support' => 'People may explore reflective and conversational wellbeing support that helps them feel clearer, steadier and more resourced.',
            'medical' => '',
            'related_modalities' => ['life-coaching', 'hypnotherapy', 'meditation'],
        ],
        'posture' => [
            'title' => 'Posture',
            'slug' => 'posture',
            'need_slug' => 'pain-management',
            'keywords' => ['posture', 'alignment', 'back', 'support'],
            'problem' => 'Posture concerns are often less about standing like a sculpture and more about moving, sitting and working with less strain.',
            'support' => 'People often look for movement-based approaches that build awareness, control and comfort in everyday positions.',
            'medical' => 'If pain is severe, sudden or getting worse, seek medical advice before starting a new movement practice.',
            'related_modalities' => ['pilates', 'yoga', 'massage-therapy'],
        ],
        'core-strength' => [
            'title' => 'Core Strength',
            'slug' => 'core-strength',
            'need_slug' => 'pain-management',
            'keywords' => ['core strength', 'stability', 'support', 'movement'],
            'problem' => 'People often search for core strength support because they want to feel steadier, more stable and more connected in how they move.',
            'support' => 'Movement-based practices may help build awareness, control and supportive strength over time when taught clearly and progressed sensibly.',
            'medical' => 'If you have an injury, severe pain or a medical condition, seek advice before starting a new movement practice.',
            'related_modalities' => ['pilates', 'yoga', 'massage-therapy'],
        ],
        'low-mood' => [
            'title' => 'Low Mood',
            'slug' => 'low-mood',
            'need_slug' => 'low-mood-burnout',
            'keywords' => ['low mood', 'mood', 'support', 'calm'],
            'problem' => 'Low mood can make even the small practical bits of life feel heavier than usual.',
            'support' => 'Some people explore supportive wellbeing practices alongside professional care to encourage grounding, routine and a sense of steadier support.',
            'medical' => '',
            'related_modalities' => ['meditation', 'reiki', 'life-coaching'],
        ],
        'motivation' => [
            'title' => 'Motivation',
            'slug' => 'motivation',
            'need_slug' => 'low-mood-burnout',
            'keywords' => ['motivation', 'momentum', 'clarity', 'support'],
            'problem' => 'When motivation disappears, it can be hard to tell whether you need a better plan, more rest or someone sensible to help untangle the two.',
            'support' => 'People often explore coaching and reflective wellbeing work to rebuild clarity, momentum and realistic next steps.',
            'medical' => '',
            'related_modalities' => ['life-coaching', 'meditation', 'breathwork'],
        ],
    ];

    private ?array $taxonomyBySlug = null;

    private ?Collection $products = null;

    private ?Collection $offerings = null;

    private ?Collection $combinedOfferings = null;

    private ?array $records = null;

    private ?array $skipLog = null;

    public function guidesHub(): array
    {
        $records = $this->publishedRecords();
        $offerings = $this->featuredOfferingCards()->take(6)->values()->all();

        $popular = collect($records)
            ->filter(fn (array $record): bool => $record['guide_type'] !== 'what_to_expect')
            ->take(8)
            ->map(fn (array $record): array => $this->hubLink($record))
            ->values()
            ->all();

        $whatIs = collect($records)
            ->where('guide_type', 'what_is')
            ->take(12)
            ->map(fn (array $record): array => $this->hubLink($record))
            ->values()
            ->all();

        $byNeed = collect(self::NEEDS)
            ->map(function (array $need): array {
                $matching = collect($this->publishedRecords())
                    ->filter(fn (array $record): bool => ($record['need_key'] ?? null) === $need['slug'])
                    ->take(4)
                    ->map(fn (array $record): array => $this->hubLink($record))
                    ->values()
                    ->all();

                return [
                    'title' => $need['title'],
                    'url' => filled($need['need_slug']) ? url('/needs/' . $need['need_slug']) : '',
                    'items' => $matching,
                ];
            })
            ->filter(fn (array $group): bool => $group['items'] !== [])
            ->take(6)
            ->values()
            ->all();

        $byModality = collect($this->supportedModalities())
            ->map(function (array $definition): array {
                return [
                    'title' => $definition['label'],
                    'url' => $this->seo()->modalityGuidesUrl($definition['format'], $definition['route_modality']),
                    'items' => collect($this->recordsForModality($definition['format'], $definition['route_modality']))
                        ->take(4)
                        ->map(fn (array $record): array => $this->hubLink($record))
                        ->values()
                        ->all(),
                ];
            })
            ->filter(fn (array $group): bool => $group['items'] !== [])
            ->take(10)
            ->values()
            ->all();

        $byFormat = collect($this->publishedFormats())
            ->map(function (string $format): array {
                $records = $this->recordsForFormat($format);

                return [
                    'title' => Str::headline($format),
                    'url' => $this->seo()->formatGuidesUrl($format),
                    'items' => collect($records)->take(5)->map(fn (array $record): array => $this->hubLink($record))->values()->all(),
                ];
            })
            ->values()
            ->all();

        $page = [
            'title' => 'Wellness Guides',
            'h1' => 'Wellness Guides',
            'intro' => 'Explore practical wellbeing guides from We Offer Wellness® covering modalities, common reasons people try them, what to expect and how to find trusted sessions online and near you. This guide hub is built to help people move from curious searching to confident browsing without wading through vague promises or filler.',
            'seo' => [
                'title' => 'Wellness Guides | Modalities, What to Expect & How to Book | We Offer Wellness®',
                'description' => 'Explore wellness guides on Reiki, breathwork, yoga, massage, sound healing and more. Learn what to expect and browse trusted sessions on We Offer Wellness®.',
                'canonical' => $this->seo()->guidesIndexUrl(),
                'robots' => 'index,follow',
                'og_type' => 'website',
            ],
            'breadcrumbs' => [
                ['label' => 'Home', 'url' => url('/')],
                ['label' => 'Guides', 'url' => $this->seo()->guidesIndexUrl()],
            ],
            'popular_guides' => $popular,
            'what_is_guides' => $whatIs,
            'guides_by_need' => $byNeed,
            'guides_by_modality' => $byModality,
            'guides_by_format' => $byFormat,
            'featured_offerings' => $offerings,
        ];

        $page['schema'] = $this->buildHubSchema($page, collect($whatIs)->take(10)->values()->all());

        return $page;
    }

    public function formatHub(string $format): ?array
    {
        $format = $this->seo()->canonicalFormatKey($format);
        $records = $this->recordsForFormat($format);
        if ($records === []) {
            return null;
        }

        $modalityGroups = collect($this->supportedModalities())
            ->where('format', $format)
            ->map(function (array $definition): array {
                return [
                    'title' => $definition['label'],
                    'url' => $this->seo()->modalityGuidesUrl($definition['format'], $definition['route_modality']),
                    'items' => collect($this->recordsForModality($definition['format'], $definition['route_modality']))
                        ->take(4)
                        ->map(fn (array $record): array => $this->hubLink($record))
                        ->values()
                        ->all(),
                ];
            })
            ->filter(fn (array $group): bool => $group['items'] !== [])
            ->values()
            ->all();

        $page = [
            'title' => Str::headline($format) . ' Guides',
            'h1' => Str::headline($format) . ' Guides',
            'intro' => 'Browse ' . strtolower(Str::headline($format)) . ' guides from We Offer Wellness® covering what each modality is, what a session or class may involve and how to find trusted options online and near you.',
            'seo' => [
                'title' => Str::headline($format) . ' Guides | We Offer Wellness®',
                'description' => 'Browse ' . strtolower(Str::headline($format)) . ' guides on We Offer Wellness®. Explore what to expect, who sessions may suit and how to find trusted options.',
                'canonical' => $this->seo()->formatGuidesUrl($format),
                'robots' => 'index,follow',
                'og_type' => 'website',
            ],
            'breadcrumbs' => [
                ['label' => 'Home', 'url' => url('/')],
                ['label' => Str::headline($format), 'url' => $this->seo()->formatPageUrl($format)],
                ['label' => 'Guides', 'url' => $this->seo()->formatGuidesUrl($format)],
            ],
            'popular_guides' => collect($records)->take(10)->map(fn (array $record): array => $this->hubLink($record))->values()->all(),
            'what_is_guides' => collect($records)->where('guide_type', 'what_is')->take(10)->map(fn (array $record): array => $this->hubLink($record))->values()->all(),
            'guides_by_need' => [],
            'guides_by_modality' => $modalityGroups,
            'guides_by_format' => [],
            'featured_offerings' => $this->featuredOfferingCards($format)->take(6)->values()->all(),
        ];

        $page['schema'] = $this->buildHubSchema($page, $page['popular_guides']);

        return $page;
    }

    public function modalityHub(string $format, string $modality): ?array
    {
        $definition = $this->findModalityByRoute($format, $modality);
        if ($definition === null) {
            return null;
        }

        $records = $this->recordsForModality($definition['format'], $definition['route_modality']);
        if ($records === []) {
            return null;
        }

        $page = [
            'title' => $definition['label'] . ' Guides',
            'h1' => $definition['label'] . ' Guides',
            'intro' => 'Browse ' . $definition['label'] . ' guides covering what it is, what to expect, common reasons people try it and how to find trusted ' . strtolower($definition['session_plural']) . ' on We Offer Wellness®.',
            'seo' => [
                'title' => $definition['label'] . ' Guides | We Offer Wellness®',
                'description' => 'Browse ' . $definition['label'] . ' guides, learn what to expect and explore trusted ' . strtolower($definition['session_plural']) . ' on We Offer Wellness®.',
                'canonical' => $this->seo()->modalityGuidesUrl($definition['format'], $definition['route_modality']),
                'robots' => 'index,follow',
                'og_type' => 'website',
            ],
            'breadcrumbs' => [
                ['label' => 'Home', 'url' => url('/')],
                ['label' => Str::headline($definition['format']), 'url' => $this->seo()->formatPageUrl($definition['format'])],
                ['label' => $this->taxonomyLabel($definition), 'url' => $this->seo()->modalityPageUrl($definition['format'], $definition['route_modality'])],
                ['label' => 'Guides', 'url' => $this->seo()->modalityGuidesUrl($definition['format'], $definition['route_modality'])],
            ],
            'popular_guides' => collect($records)->take(12)->map(fn (array $record): array => $this->hubLink($record))->values()->all(),
            'what_is_guides' => collect($records)->where('guide_type', 'what_is')->map(fn (array $record): array => $this->hubLink($record))->values()->all(),
            'guides_by_need' => [],
            'guides_by_modality' => [],
            'guides_by_format' => [],
            'featured_offerings' => $this->featuredOfferingCards($definition['format'], $definition)->take(6)->values()->all(),
            'modality_links' => $this->nearbyLinks($definition),
        ];

        $page['schema'] = $this->buildHubSchema($page, $page['popular_guides'], $definition);

        return $page;
    }

    public function modalityGuidePanel(string $modality, ?string $preferredFormat = null): ?array
    {
        $modality = Str::slug($modality);
        if ($modality === '') {
            return null;
        }

        $definition = null;

        if ($preferredFormat !== null && $preferredFormat !== '') {
            $definition = $this->findModalityByRoute($preferredFormat, $modality);
        }

        if ($definition === null) {
            $definition = collect($this->supportedModalities())
                ->first(fn (array $candidate): bool => $candidate['route_modality'] === $modality);
        }

        if ($definition === null) {
            return null;
        }

        $records = $this->recordsForModality($definition['format'], $definition['route_modality']);
        if ($records === []) {
            return null;
        }

        return [
            'title' => $definition['label'] . ' Guides',
            'eyebrow' => 'Explore guides',
            'summary' => 'Learn what ' . $definition['label'] . ' is, what to expect and how it may support different needs before you book.',
            'hub_url' => $this->seo()->modalityGuidesUrl($definition['format'], $definition['route_modality']),
            'hub_label' => 'Browse all ' . $definition['label'] . ' guides',
            'links' => collect($records)
                ->take(4)
                ->map(fn (array $record): array => $this->hubLink($record))
                ->values()
                ->all(),
        ];
    }

    public function guidePage(string $format, string $modality, string $guideSlug): ?array
    {
        $record = collect($this->publishedRecords())->first(function (array $record) use ($format, $modality, $guideSlug): bool {
            return $record['format'] === $this->seo()->canonicalFormatKey($format)
                && $record['route_modality'] === Str::slug($modality)
                && $record['slug'] === Str::slug($guideSlug);
        });

        if ($record === null) {
            return null;
        }

        return $this->buildGuidePage($record);
    }

    public function publishedGuideEntries(): array
    {
        $entries = [];
        $records = $this->publishedRecords();

        $this->pushEntry($entries, $this->seo()->guidesIndexUrl(), self::PUBLISHED_AT);

        foreach ($this->publishedFormats() as $format) {
            $this->pushEntry($entries, $this->seo()->formatGuidesUrl($format), self::PUBLISHED_AT);
        }

        foreach ($this->supportedModalities() as $definition) {
            if ($this->recordsForModality($definition['format'], $definition['route_modality']) === []) {
                continue;
            }

            $this->pushEntry($entries, $this->seo()->modalityGuidesUrl($definition['format'], $definition['route_modality']), self::PUBLISHED_AT);
        }

        foreach ($records as $record) {
            $page = $this->buildGuidePage($record);
            if (!$this->passesValidation($page)) {
                continue;
            }

            $this->pushEntry($entries, $page['seo']['canonical'], $page['updated_at'] ?? self::PUBLISHED_AT);
        }

        return array_values($entries);
    }

    public function legacyRedirects(): array
    {
        $redirects = [];

        foreach ($this->publishedRecords() as $record) {
            $target = $this->relativeGuidePath($record);

            $redirects['/' . $record['slug']] = $target;

            if ($record['guide_type'] === 'what_is') {
                $redirects['/' . $record['slug']] = $target;
                $redirects['/' . $record['guide_slug_base'] . '-benefits'] = $target;
            }

            if ($record['guide_type'] === 'modality_for_need') {
                $redirects['/' . $record['guide_slug_base'] . '-for-' . $record['need_slug']] = $target;
            }
        }

        $redirects['/what-is-reiki'] = '/therapies/reiki/guides/what-is-reiki';
        $redirects['/reiki-benefits'] = '/therapies/reiki/guides/what-is-reiki';
        $redirects['/what-is-reflexology'] = '/therapies/reflexology/guides/what-is-reflexology';
        $redirects['/yoga-for-back-pain'] = '/classes/yoga/guides/yoga-for-back-pain';

        return $redirects;
    }

    public function skipLog(): array
    {
        if ($this->skipLog !== null) {
            return $this->skipLog;
        }

        $available = $this->taxonomyBySlug();
        $skipped = [];

        foreach (self::MODALITIES as $key => $definition) {
            $routeModality = $definition['route_modality'];
            if (!array_key_exists($routeModality, $available)) {
                $skipped[] = [
                    'label' => $definition['label'],
                    'reason' => 'Missing modality taxonomy slug `' . $routeModality . '`.',
                ];
            }
        }

        foreach ([
            ['label' => 'Counselling', 'slug' => 'counselling'],
            ['label' => 'Somatic Therapy', 'slug' => 'somatic-therapy'],
        ] as $missing) {
            if (!array_key_exists($missing['slug'], $available)) {
                $skipped[] = [
                    'label' => $missing['label'],
                    'reason' => 'Requested guide skipped because taxonomy slug `' . $missing['slug'] . '` is not available in the live modality catalogue.',
                ];
            }
        }

        return $this->skipLog = $skipped;
    }

    private function buildGuidePage(array $record): array
    {
        return match ($record['guide_type']) {
            'what_is' => $this->buildWhatIsPage($record),
            'modality_for_need' => $this->buildNeedGuidePage($record),
            'what_to_expect' => $this->buildWhatToExpectPage($record),
            'custom' => $this->buildCustomGuidePage($record),
            default => [],
        };
    }

    private function buildCustomGuidePage(array $record): array
    {
        $definition = $record['modality'];
        $canonical = $this->absoluteGuideUrl($record);
        $safety = $record['safety_note'] ?: $this->safetyNote($definition['safety_tags'], null);
        $faqs = array_values(array_filter((array) ($record['faqs'] ?? []), fn (mixed $faq): bool => is_array($faq) && filled($faq['q'] ?? null) && filled($faq['a'] ?? null)));

        $page = [
            'guide_type' => 'custom',
            'title' => $record['title'],
            'h1' => $record['title'],
            'format' => $record['format'],
            'modality' => $record['route_modality'],
            'modality_label' => $definition['label'],
            'guide_slug_base' => $definition['guide_slug_base'],
            'slug' => $record['slug'],
            'intro' => $record['intro'],
            'quick_answer' => $record['quick_answer'],
            'sections' => array_values($record['sections'] ?? []),
            'faqs' => $faqs,
            'safety_note' => $safety,
            'offerings' => $this->offeringCards($definition),
            'nearby_links' => $this->nearbyLinks($definition),
            'online_links' => $this->onlineLinks($definition),
            'related_guides' => $this->relatedGuidesForRecord($record),
            'practitioners' => $this->practitionerCards($definition),
            'final_cta' => [
                'heading' => 'Explore ' . $definition['label'],
                'text' => 'Compare current options and decide whether this approach feels like the right fit for you.',
                'links' => [
                    ['label' => 'Browse ' . $definition['label'], 'url' => $this->seo()->modalityPageUrl($definition['format'], $definition['route_modality'])],
                    ['label' => 'Find ' . $definition['label'] . ' near you', 'url' => $this->seo()->modalityPageUrl($definition['format'], $definition['route_modality'])],
                ],
            ],
            'published_at' => $record['published_at'] ?? self::PUBLISHED_AT,
            'updated_at' => $record['updated_at'] ?? self::PUBLISHED_AT,
            'breadcrumbs' => $this->breadcrumbsForRecord($record),
            'seo' => [
                'title' => $record['seo_title'] ?: $record['title'] . ' | We Offer Wellness®',
                'description' => $record['seo_description'] ?: $record['summary'],
                'canonical' => $canonical,
                'robots' => 'index,follow',
                'og_type' => 'article',
            ],
        ];
        $page['schema'] = $this->buildGuideSchema($page, $definition);

        return $page;
    }

    private function buildWhatIsPage(array $record): array
    {
        $definition = $record['modality'];
        $canonical = $this->absoluteGuideUrl($record);
        $offerings = $this->offeringCards($definition);
        $related = $this->relatedGuidesForRecord($record);
        $nearby = $this->nearbyLinks($definition);
        $online = $this->onlineLinks($definition);
        $practitioners = $this->practitionerCards($definition);
        $safety = $this->safetyNote($definition['safety_tags'], null);

        $page = [
            'guide_type' => 'what_is',
            'title' => 'What Is ' . $definition['label'] . '?',
            'h1' => 'What Is ' . $definition['label'] . '?',
            'format' => $definition['format'],
            'modality' => $definition['route_modality'],
            'modality_label' => $definition['label'],
            'guide_slug_base' => $definition['guide_slug_base'],
            'slug' => $record['slug'],
            'intro' => sprintf(
                '%s is %s. People often search for it when they want a clearer sense of what a session involves, what it may support and whether it suits them as a beginner. This guide explains the basics in plain English, without the theatrical claims or mystical fog. We Offer Wellness® also helps you move from curiosity to action by showing trusted %s, online options, nearby discovery pages and practitioner profiles in one place.',
                $definition['label'],
                $definition['summary'],
                strtolower($definition['session_plural'])
            ),
            'quick_answer' => sprintf(
                '%s is %s. People often try it for %s, and sessions can vary by practitioner, pace and style. It may support relaxation, grounding or body awareness, but it should not replace medical care when that is needed.',
                $definition['label'],
                $definition['summary'],
                strtolower($definition['uses'])
            ),
            'sections' => [
                [
                    'id' => 'what-is-' . $definition['guide_slug_base'],
                    'heading' => 'What is ' . $definition['label'] . '?',
                    'paragraphs' => [
                        sprintf('%s is %s. In practice, that means the experience is usually designed to help you slow down, pay attention to how you feel and spend time with a practitioner, teacher or facilitator whose style suits you.', $definition['label'], $definition['summary']),
                        'The exact shape of a session depends on the modality and the practitioner. Some are hands-on, some are movement-based and some are more reflective or immersive. The useful question is not whether one label sounds magical enough. It is whether the session style fits what you actually need.',
                    ],
                ],
                [
                    'id' => 'where-did-it-come-from',
                    'heading' => 'Where did ' . $definition['label'] . ' come from?',
                    'paragraphs' => [
                        $definition['origin'],
                        'Like many wellbeing practices, the way it is offered today can vary from traditional roots to modern studio, clinic or event formats. That is why it helps to look at the practitioner’s explanation rather than assuming every listing means exactly the same thing.',
                    ],
                ],
                [
                    'id' => 'what-happens-during-session',
                    'heading' => 'What happens during a ' . $definition['label'] . ' ' . $definition['session_noun'] . '?',
                    'paragraphs' => [
                        $definition['session'],
                        'Most good sessions also include a quick check-in before you begin and a little space to ask questions afterwards. If you are brand new, that practical chat is often the bit that makes everything feel much more normal.',
                    ],
                ],
                [
                    'id' => 'what-do-people-use-it-for',
                    'heading' => 'What do people use ' . $definition['label'] . ' for?',
                    'paragraphs' => [
                        $definition['uses'],
                        'People may use it as part of a broader wellbeing routine, alongside rest, movement, therapy, medical support or lifestyle changes. It is usually most helpful when described honestly as support rather than as a cure-all with excellent branding.',
                    ],
                ],
                [
                    'id' => 'is-it-suitable-for-beginners',
                    'heading' => 'Is ' . $definition['label'] . ' suitable for beginners?',
                    'paragraphs' => [
                        $definition['beginner'],
                        'If you are unsure, start with a practitioner or class description that feels clear and accessible rather than advanced, intense or deliberately mysterious.',
                    ],
                ],
                [
                    'id' => 'choose-a-practitioner',
                    'heading' => 'How to choose a trusted practitioner',
                    'paragraphs' => [
                        $definition['chooser'],
                        'On We Offer Wellness®, you can compare listings, read profile information and check whether the offer is online, in person, one-to-one or group-based before deciding what feels right.',
                    ],
                ],
                [
                    'id' => 'browse-offerings',
                    'heading' => 'Browse ' . $definition['label'] . ' ' . strtolower($definition['session_plural']) . ' on We Offer Wellness®',
                    'paragraphs' => [
                        'If you already know the modality sounds promising, the next sensible step is comparing real listings. Look at the session style, the setting, whether it is online or in person, and how clearly the practitioner describes what they offer.',
                    ],
                ],
                [
                    'id' => 'find-near-you',
                    'heading' => 'Find ' . $definition['label'] . ' near you',
                    'paragraphs' => [
                        'If you prefer something nearby, start with the main UK page and then narrow down to county or town pages. That gives you a cleaner route into relevant local options than endless tabs and vague map pins.',
                    ],
                ],
            ],
            'faqs' => [
                ['q' => 'Is ' . $definition['label'] . ' suitable for beginners?', 'a' => $definition['beginner']],
                ['q' => 'What should I expect in a ' . $definition['label'] . ' ' . $definition['session_noun'] . '?', 'a' => $definition['session']],
                ['q' => 'Do I need to prepare before ' . $definition['label'] . '?', 'a' => 'Most sessions need very little preparation. Comfortable clothing, a few minutes to arrive calmly and any useful health information are usually enough.'],
                ['q' => 'Can I book ' . $definition['label'] . ' online?', 'a' => 'Many modalities on We Offer Wellness® include online options. If online sessions are available for this modality, you will find them in the online section below.'],
                ['q' => 'How do I find ' . $definition['label'] . ' near me?', 'a' => 'Use the nearby links on this page to browse UK, county and town-level discovery pages for this modality.'],
            ],
            'safety_note' => $safety,
            'offerings' => $offerings,
            'nearby_links' => $nearby,
            'online_links' => $online,
            'related_guides' => $related,
            'practitioners' => $practitioners,
            'final_cta' => [
                'heading' => 'Ready to explore ' . $definition['label'] . '?',
                'text' => 'Browse live options, compare trusted practitioners and decide whether this feels like the right next step for you.',
                'links' => [
                    ['label' => 'Browse ' . $definition['label'] . ' ' . strtolower($definition['session_plural']), 'url' => $this->seo()->modalityPageUrl($definition['format'], $definition['route_modality'])],
                    ['label' => 'Find ' . $definition['label'] . ' near you', 'url' => $nearby[0]['url'] ?? $this->seo()->modalityPageUrl($definition['format'], $definition['route_modality'])],
                    ['label' => 'Explore online ' . $definition['label'], 'url' => $online[0]['url'] ?? url('/online/' . $definition['route_modality'])],
                    ['label' => 'Meet trusted practitioners', 'url' => $practitioners[0]['url'] ?? $this->seo()->modalityPageUrl($definition['format'], $definition['route_modality'])],
                ],
            ],
            'published_at' => self::PUBLISHED_AT,
            'updated_at' => self::PUBLISHED_AT,
            'breadcrumbs' => $this->breadcrumbsForRecord($record),
            'seo' => [
                'title' => 'What Is ' . $definition['label'] . '? Benefits, What to Expect & How to Book | We Offer Wellness®',
                'description' => 'Learn what ' . $definition['label'] . ' is, what to expect in a ' . $definition['session_noun'] . ', who it may suit and how to find trusted ' . strtolower($definition['session_plural']) . ' on We Offer Wellness®.',
                'canonical' => $canonical,
                'robots' => 'index,follow',
                'og_type' => 'article',
            ],
        ];

        $page['schema'] = $this->buildGuideSchema($page, $definition);

        return $page;
    }

    private function buildNeedGuidePage(array $record): array
    {
        $definition = $record['modality'];
        $need = self::NEEDS[$record['need_key']] ?? null;
        if ($need === null) {
            return [];
        }

        $canonical = $this->absoluteGuideUrl($record);
        $offerings = $this->offeringCards($definition, $need);
        $related = $this->relatedGuidesForRecord($record);
        $nearby = $this->nearbyLinks($definition);
        $online = $this->onlineLinks($definition);
        $practitioners = $this->practitionerCards($definition, $need);
        $safety = $this->safetyNote($definition['safety_tags'], $need);

        $page = [
            'guide_type' => 'modality_for_need',
            'title' => 'How Can ' . $definition['label'] . ' Help with ' . $need['title'] . '?',
            'h1' => 'How Can ' . $definition['label'] . ' Help with ' . $need['title'] . '?',
            'format' => $definition['format'],
            'modality' => $definition['route_modality'],
            'modality_label' => $definition['label'],
            'guide_slug_base' => $definition['guide_slug_base'],
            'slug' => $record['slug'],
            'need_key' => $need['slug'],
            'intro' => sprintf(
                '%s People often look at %s when they want a complementary approach that may support them alongside the rest of their wellbeing routine. This guide explains what people tend to try it for, what a %s %s may involve and how to compare trusted options on We Offer Wellness® without drifting into overclaim territory.',
                $need['problem'],
                $definition['label'],
                strtolower($definition['label']),
                $definition['session_noun']
            ),
            'quick_answer' => sprintf(
                '%s may help some people with %s by supporting relaxation, body awareness, steadier breathing or reflective calm, depending on the modality and the person. It is best viewed as complementary support rather than a replacement for medical or mental health care.',
                $definition['label'],
                strtolower($need['title'])
            ),
            'sections' => [
                [
                    'id' => 'can-it-help',
                    'heading' => 'Can ' . $definition['label'] . ' help with ' . $need['title'] . '?',
                    'paragraphs' => [
                        sprintf('%s %s', $need['support'], 'How much support someone feels can depend on the practitioner, the style of session, how regularly they try it and what else is going on for them.'),
                        $definition['label'] . ' may support relaxation, grounding or helpful awareness around how you are feeling. It should not be framed as a guaranteed fix, because real bodies and real lives are not built that way.',
                    ],
                ],
                [
                    'id' => 'why-people-try-it',
                    'heading' => 'Why people try ' . $definition['label'] . ' for ' . $need['title'],
                    'paragraphs' => [
                        'People often explore this modality because they want support that feels practical, embodied or restorative, especially when stress, discomfort or mental noise have started taking up too much room.',
                        $definition['uses'],
                    ],
                ],
                [
                    'id' => 'what-happens',
                    'heading' => 'What happens in a ' . $definition['label'] . ' ' . $definition['session_noun'] . '?',
                    'paragraphs' => [
                        $definition['session'],
                        'If you are booking specifically with ' . strtolower($need['title']) . ' in mind, it helps to tell the practitioner that up front so they can explain whether the modality and pace make sense for you.',
                    ],
                ],
                [
                    'id' => 'how-often',
                    'heading' => 'How often might people try it?',
                    'paragraphs' => [
                        'That varies. Some people try one session as a starting point, while others build it into a broader routine over several weeks. The most useful practitioners tend to discuss pace honestly rather than pretending every problem needs an immediate package.',
                    ],
                ],
                [
                    'id' => 'what-to-look-for',
                    'heading' => 'What to look for in a practitioner',
                    'paragraphs' => [
                        $definition['chooser'],
                        'If your main aim is support around ' . strtolower($need['title']) . ', look for someone who explains how they adapt sessions, how they think about suitability and when they would suggest extra professional support.',
                    ],
                ],
                [
                    'id' => 'seek-professional-help',
                    'heading' => 'When to seek medical or professional help',
                    'paragraphs' => array_values(array_filter([
                        'Complementary wellbeing practices should not replace medical advice, diagnosis or treatment. If you are dealing with ongoing pain, anxiety, low mood, trauma symptoms or a medical condition, speak to a qualified healthcare professional.',
                        $need['medical'] ?: null,
                    ])),
                ],
                [
                    'id' => 'browse-offerings',
                    'heading' => 'Browse ' . $definition['label'] . ' offerings for ' . $need['title'],
                    'paragraphs' => [
                        'Compare available listings by style, setting and format. If there are not many exact matches for this need, browsing the broader modality can still help you find a practitioner whose description fits what you are looking for.',
                    ],
                ],
                [
                    'id' => 'find-near-you',
                    'heading' => 'Find ' . $definition['label'] . ' near you',
                    'paragraphs' => [
                        'Use the nearby links to move from the national page to county and town-level discovery. It is a tidier route into relevant options than searching a vague phrase and hoping the algorithm is feeling kind.',
                    ],
                ],
                [
                    'id' => 'related-approaches',
                    'heading' => 'Related wellbeing approaches',
                    'paragraphs' => [
                        'Sometimes the right next step is comparing a few modalities rather than assuming the first one you found must be the answer. Related guides below can help you explore other approaches people often consider for this kind of support.',
                    ],
                ],
            ],
            'faqs' => [
                ['q' => 'Can ' . $definition['label'] . ' help with ' . $need['title'] . '?', 'a' => $definition['label'] . ' may support some people with ' . strtolower($need['title']) . ', depending on the style of session, the practitioner and the wider context.'],
                ['q' => 'How often might people try ' . $definition['label'] . ' for ' . $need['title'] . '?', 'a' => 'Some people try a single session first, while others build a short series into a broader wellbeing routine.'],
                ['q' => 'Is ' . $definition['label'] . ' safe for everyone?', 'a' => $safety],
                ['q' => 'Can I do ' . $definition['label'] . ' online?', 'a' => 'Some modalities offer helpful online formats, while others are mainly in-person. Check the online options section on this page for current availability.'],
                ['q' => 'When should I seek medical advice?', 'a' => 'If symptoms are severe, worsening, long-lasting or affecting daily life significantly, speak to a qualified healthcare professional.'],
            ],
            'safety_note' => $safety,
            'offerings' => $offerings,
            'nearby_links' => $nearby,
            'online_links' => $online,
            'related_guides' => $related,
            'practitioners' => $practitioners,
            'final_cta' => [
                'heading' => 'Compare trusted ' . $definition['label'] . ' options',
                'text' => 'Use the links below to browse live listings, online options and nearby discovery pages with this support aim in mind.',
                'links' => [
                    ['label' => 'Browse ' . $definition['label'] . ' for ' . $need['title'], 'url' => $this->seo()->modalityPageUrl($definition['format'], $definition['route_modality'])],
                    ['label' => 'Find ' . $definition['label'] . ' near you', 'url' => $nearby[0]['url'] ?? $this->seo()->modalityPageUrl($definition['format'], $definition['route_modality'])],
                    ['label' => 'Explore online ' . $definition['label'], 'url' => $online[0]['url'] ?? url('/online/' . $definition['route_modality'])],
                    ['label' => 'Compare trusted practitioners', 'url' => $practitioners[0]['url'] ?? $this->seo()->modalityPageUrl($definition['format'], $definition['route_modality'])],
                ],
            ],
            'published_at' => self::PUBLISHED_AT,
            'updated_at' => self::PUBLISHED_AT,
            'breadcrumbs' => $this->breadcrumbsForRecord($record),
            'seo' => [
                'title' => $definition['label'] . ' for ' . $need['title'] . ' | How It May Help & What to Expect | We Offer Wellness®',
                'description' => 'Explore how ' . $definition['label'] . ' may support ' . strtolower($need['title']) . ', what to expect in a ' . $definition['session_noun'] . ' and how to find trusted options on We Offer Wellness®.',
                'canonical' => $canonical,
                'robots' => 'index,follow',
                'og_type' => 'article',
            ],
        ];

        $page['schema'] = $this->buildGuideSchema($page, $definition);

        return $page;
    }

    private function buildWhatToExpectPage(array $record): array
    {
        $definition = $record['modality'];
        $canonical = $this->absoluteGuideUrl($record);
        $offerings = $this->offeringCards($definition);
        $related = $this->relatedGuidesForRecord($record);
        $nearby = $this->nearbyLinks($definition);
        $online = $this->onlineLinks($definition);
        $practitioners = $this->practitionerCards($definition);
        $safety = $this->safetyNote($definition['safety_tags'], null);

        $page = [
            'guide_type' => 'what_to_expect',
            'title' => $definition['expect_title'],
            'h1' => $definition['expect_title'],
            'format' => $definition['format'],
            'modality' => $definition['route_modality'],
            'modality_label' => $definition['label'],
            'guide_slug_base' => $definition['guide_slug_base'],
            'slug' => $record['slug'],
            'intro' => 'If you are curious about booking ' . strtolower($definition['label']) . ' but would quite like to know what actually happens first, this page covers the basics. It explains the usual flow of a session, what to bring, what you might feel afterwards and how We Offer Wellness® helps you compare trusted options before booking.',
            'quick_answer' => 'Most ' . strtolower($definition['label']) . ' ' . $definition['session_plural'] . ' start with a short conversation, move into the main practice and finish with a few minutes to land and ask questions. The exact structure varies, but a clear practitioner should explain the format before you begin.',
            'sections' => [
                ['id' => 'before-you-arrive', 'heading' => 'Before you arrive', 'paragraphs' => ['Many sessions begin with a simple check-in covering your aims, any relevant health information and what the format involves. Comfortable clothing and a few calm minutes beforehand are usually enough preparation.']],
                ['id' => 'during-the-session', 'heading' => 'During the session', 'paragraphs' => [$definition['session'], 'You should feel able to ask questions, mention discomfort and check the pace if needed.']],
                ['id' => 'afterwards', 'heading' => 'How you might feel afterwards', 'paragraphs' => ['Some people feel calm, sleepy, clearer or simply more settled. Others feel much the same, which is also a normal human response and not a personal failure.']],
                ['id' => 'questions-to-ask', 'heading' => 'Useful questions to ask', 'paragraphs' => ['Ask about session length, style, pressure or intensity, what is expected of you and whether the session can be adapted for comfort or health considerations.']],
                ['id' => 'finding-the-right-fit', 'heading' => 'Finding the right fit', 'paragraphs' => [$definition['chooser'], 'Comparing the practitioner’s tone and session style can matter as much as choosing the modality itself.']],
            ],
            'faqs' => [
                ['q' => 'Do I need experience before booking?', 'a' => $definition['beginner']],
                ['q' => 'Should I prepare anything in advance?', 'a' => 'Usually just comfortable clothing, any useful health details and enough time to arrive without a sprint finish.'],
                ['q' => 'Will the session be the same with every practitioner?', 'a' => 'No. Style, pacing and structure can vary, which is why reading the listing carefully is worthwhile.'],
            ],
            'safety_note' => $safety,
            'offerings' => $offerings,
            'nearby_links' => $nearby,
            'online_links' => $online,
            'related_guides' => $related,
            'practitioners' => $practitioners,
            'final_cta' => [
                'heading' => 'Browse live ' . strtolower($definition['session_plural']),
                'text' => 'Compare formats, practitioner styles and availability before booking.',
                'links' => [
                    ['label' => 'Browse ' . $definition['label'] . ' ' . strtolower($definition['session_plural']), 'url' => $this->seo()->modalityPageUrl($definition['format'], $definition['route_modality'])],
                    ['label' => 'Find ' . $definition['label'] . ' near you', 'url' => $nearby[0]['url'] ?? $this->seo()->modalityPageUrl($definition['format'], $definition['route_modality'])],
                ],
            ],
            'published_at' => self::PUBLISHED_AT,
            'updated_at' => self::PUBLISHED_AT,
            'breadcrumbs' => $this->breadcrumbsForRecord($record),
            'seo' => [
                'title' => $definition['expect_title'] . ' | We Offer Wellness®',
                'description' => 'Learn what to expect from ' . strtolower($definition['label']) . ', how a ' . $definition['session_noun'] . ' usually flows and how to compare trusted options on We Offer Wellness®.',
                'canonical' => $canonical,
                'robots' => 'index,follow',
                'og_type' => 'article',
            ],
        ];

        $page['schema'] = $this->buildGuideSchema($page, $definition);

        return $page;
    }

    private function buildGuideSchema(array $page, array $definition): array
    {
        $canonical = $page['seo']['canonical'];
        $organizationId = url('/') . '#organization';
        $websiteId = url('/') . '#website';
        $breadcrumbId = $canonical . '#breadcrumb';
        $webpageId = $canonical . '#webpage';
        $articleId = $canonical . '#article';
        $modalityId = $this->seo()->modalityPageUrl($definition['format'], $definition['route_modality']) . '#modality';
        $logoUrl = 'https://studio.weofferwellness.co.uk/storage/uploads/images/e9dc87f9-01bf-4ffd-be8f-e1f49a85bf41.png';

        $graph = [
            [
                '@type' => 'Organization',
                '@id' => $organizationId,
                'name' => 'We Offer Wellness®',
                'url' => url('/'),
                'logo' => [
                    '@type' => 'ImageObject',
                    '@id' => url('/') . '#logo',
                    'url' => $logoUrl,
                ],
            ],
            [
                '@type' => 'WebSite',
                '@id' => $websiteId,
                'url' => url('/'),
                'name' => 'We Offer Wellness®',
                'publisher' => ['@id' => $organizationId],
                'inLanguage' => 'en-GB',
            ],
            [
                '@type' => 'DefinedTerm',
                '@id' => $modalityId,
                'name' => $definition['label'],
                'termCode' => $definition['route_modality'],
                'inDefinedTermSet' => 'Wellness Modalities',
            ],
            [
                '@type' => 'WebPage',
                '@id' => $webpageId,
                'url' => $canonical,
                'name' => $page['seo']['title'],
                'description' => $page['seo']['description'],
                'isPartOf' => ['@id' => $websiteId],
                'publisher' => ['@id' => $organizationId],
                'about' => ['@id' => $modalityId],
                'mainEntity' => ['@id' => $articleId],
                'breadcrumb' => ['@id' => $breadcrumbId],
                'inLanguage' => 'en-GB',
            ],
            [
                '@type' => 'Article',
                '@id' => $articleId,
                'headline' => $page['h1'],
                'description' => $page['seo']['description'],
                'author' => ['@id' => $organizationId],
                'publisher' => ['@id' => $organizationId],
                'mainEntityOfPage' => ['@id' => $webpageId],
                'datePublished' => $page['published_at'],
                'dateModified' => $page['updated_at'],
                'inLanguage' => 'en-GB',
            ],
        ];

        if ($page['offerings'] !== []) {
            $graph[] = [
                '@type' => 'ItemList',
                '@id' => $canonical . '#related-services',
                'name' => 'Related ' . $definition['label'] . ' services',
                'itemListElement' => collect($page['offerings'])
                    ->values()
                    ->take(8)
                    ->map(function (array $item, int $index): array {
                        $service = [
                            '@type' => 'Service',
                            '@id' => $item['url'] . '#service',
                            'name' => $item['title'],
                            'url' => $item['url'],
                            'serviceType' => $item['service_type'] ?? 'Wellness service',
                            'category' => 'Wellness service',
                            'broker' => ['@id' => url('/') . '#organization'],
                        ];

                        if (!empty($item['price'])) {
                            $service['offers'] = [
                                '@type' => 'Offer',
                                'url' => $item['url'],
                                'priceCurrency' => 'GBP',
                                'price' => $item['price'],
                                'availability' => 'https://schema.org/InStock',
                                'seller' => ['@id' => url('/') . '#organization'],
                            ];
                        }

                        return [
                            '@type' => 'ListItem',
                            'position' => $index + 1,
                            'url' => $item['url'],
                            'item' => $service,
                        ];
                    })
                    ->values()
                    ->all(),
            ];
        }

        $graph[] = [
            '@type' => 'BreadcrumbList',
            '@id' => $breadcrumbId,
            'itemListElement' => collect($page['breadcrumbs'])
                ->values()
                ->map(function (array $crumb, int $index): array {
                    return [
                        '@type' => 'ListItem',
                        'position' => $index + 1,
                        'name' => $crumb['label'],
                        'item' => $crumb['url'] ?? '',
                    ];
                })
                ->values()
                ->all(),
        ];

        if ($page['faqs'] !== []) {
            $graph[] = [
                '@type' => 'FAQPage',
                '@id' => $canonical . '#faq',
                'mainEntity' => collect($page['faqs'])->map(function (array $faq): array {
                    return [
                        '@type' => 'Question',
                        'name' => $faq['q'],
                        'acceptedAnswer' => [
                            '@type' => 'Answer',
                            'text' => $faq['a'],
                        ],
                    ];
                })->values()->all(),
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@graph' => $graph,
        ];
    }

    private function buildHubSchema(array $page, array $items, ?array $definition = null): array
    {
        $canonical = $page['seo']['canonical'];
        $organizationId = url('/') . '#organization';
        $websiteId = url('/') . '#website';
        $logoUrl = 'https://studio.weofferwellness.co.uk/storage/uploads/images/e9dc87f9-01bf-4ffd-be8f-e1f49a85bf41.png';

        $graph = [
            [
                '@type' => 'Organization',
                '@id' => $organizationId,
                'name' => 'We Offer Wellness®',
                'url' => url('/'),
                'logo' => [
                    '@type' => 'ImageObject',
                    '@id' => url('/') . '#logo',
                    'url' => $logoUrl,
                ],
            ],
            [
                '@type' => 'WebSite',
                '@id' => $websiteId,
                'url' => url('/'),
                'name' => 'We Offer Wellness®',
                'publisher' => ['@id' => $organizationId],
                'inLanguage' => 'en-GB',
            ],
            [
                '@type' => 'CollectionPage',
                '@id' => $canonical . '#webpage',
                'url' => $canonical,
                'name' => $page['seo']['title'],
                'description' => $page['seo']['description'],
                'isPartOf' => ['@id' => $websiteId],
                'publisher' => ['@id' => $organizationId],
                'breadcrumb' => ['@id' => $canonical . '#breadcrumb'],
                'inLanguage' => 'en-GB',
            ],
            [
                '@type' => 'ItemList',
                '@id' => $canonical . '#guides',
                'name' => $page['title'],
                'itemListElement' => collect($items)->values()->take(12)->map(function (array $item, int $index): array {
                    return [
                        '@type' => 'ListItem',
                        'position' => $index + 1,
                        'url' => $item['url'],
                        'name' => $item['title'] ?? $item['label'],
                    ];
                })->values()->all(),
            ],
            [
                '@type' => 'BreadcrumbList',
                '@id' => $canonical . '#breadcrumb',
                'itemListElement' => collect($page['breadcrumbs'])->values()->map(function (array $crumb, int $index): array {
                    return [
                        '@type' => 'ListItem',
                        'position' => $index + 1,
                        'name' => $crumb['label'],
                        'item' => $crumb['url'],
                    ];
                })->values()->all(),
            ],
        ];

        if ($definition !== null) {
            array_splice($graph, 2, 0, [[
                '@type' => 'DefinedTerm',
                '@id' => $this->seo()->modalityPageUrl($definition['format'], $definition['route_modality']) . '#modality',
                'name' => $definition['label'],
                'termCode' => $definition['route_modality'],
                'inDefinedTermSet' => 'Wellness Modalities',
            ]]);
        }

        return [
            '@context' => 'https://schema.org',
            '@graph' => $graph,
        ];
    }

    private function hubLink(array $record): array
    {
        $title = $record['title'];
        $summary = $record['summary'] ?? '';

        return [
            'title' => $title,
            'label' => $title,
            'summary' => $summary,
            'url' => $this->absoluteGuideUrl($record),
        ];
    }

    private function relatedGuidesForRecord(array $record): array
    {
        $records = collect($this->publishedRecords());
        $sameModality = $records
            ->filter(fn (array $candidate): bool => $candidate['route_modality'] === $record['route_modality'] && $candidate['slug'] !== $record['slug'])
            ->take(5);

        $sameNeed = $record['guide_type'] === 'modality_for_need'
            ? $records->filter(fn (array $candidate): bool => ($candidate['need_key'] ?? null) === ($record['need_key'] ?? null) && $candidate['slug'] !== $record['slug'])->take(4)
            : collect();

        return $sameModality
            ->merge($sameNeed)
            ->unique('slug')
            ->map(function (array $candidate): array {
                return [
                    'title' => $candidate['title'],
                    'url' => $this->absoluteGuideUrl($candidate),
                    'summary' => $candidate['summary'] ?? '',
                ];
            })
            ->values()
            ->all();
    }

    private function nearbyLinks(array $definition): array
    {
        $base = rtrim($this->seo()->modalityPageUrl($definition['format'], $definition['route_modality']), '/');

        return [
            ['label' => 'United Kingdom', 'url' => $base . '/united-kingdom'],
            ['label' => 'Kent', 'url' => $base . '/united-kingdom/kent'],
            ['label' => 'Chatham', 'url' => $base . '/united-kingdom/kent/chatham'],
        ];
    }

    private function onlineLinks(array $definition): array
    {
        return [
            [
                'label' => 'Explore online ' . $definition['label'],
                'url' => url('/online/' . $definition['route_modality']),
            ],
        ];
    }

    private function practitionerCards(array $definition, ?array $need = null): array
    {
        return $this->offeringPool($definition, $need)
            ->map(function (array $item): ?array {
                $user = $item['user'] ?? null;
                if ($user === null) {
                    return null;
                }

                return [
                    'name' => trim((string) ($user->public_display_name ?? $user->full_name ?? $user->name ?? 'Practitioner')),
                    'url' => $user->practitioner_profile_url ?? '',
                ];
            })
            ->filter(fn (?array $item): bool => is_array($item) && filled($item['url']))
            ->unique('url')
            ->take(4)
            ->values()
            ->all();
    }

    private function offeringCards(array $definition, ?array $need = null): array
    {
        return $this->offeringPool($definition, $need)
            ->map(function (array $item) use ($definition): array {
                return [
                    'title' => $item['title'],
                    'url' => $item['url'],
                    'summary' => $item['summary'],
                    'price' => $item['price'],
                    'service_type' => $definition['label'],
                    'practitioner' => $item['practitioner'],
                ];
            })
            ->take(6)
            ->values()
            ->all();
    }

    private function offeringPool(array $definition, ?array $need = null): Collection
    {
        $categorySlugs = array_values(array_unique(array_filter([
            $definition['route_modality'],
            Str::slug($definition['label']),
        ])));

        return $this->combinedOfferings()
            ->filter(function (array $item) use ($categorySlugs, $need): bool {
                if (!in_array($item['category_slug'], $categorySlugs, true)) {
                    return false;
                }

                if ($need === null) {
                    return true;
                }

                if ($item['need_slugs'] !== [] && in_array($need['need_slug'], $item['need_slugs'], true)) {
                    return true;
                }

                return $this->textContainsAny($item['search_text'], $need['keywords']);
            })
            ->sortByDesc('updated_at')
            ->values();
    }

    private function combinedOfferings(): Collection
    {
        if ($this->combinedOfferings !== null) {
            return $this->combinedOfferings;
        }

        $seo = $this->seo();

        $products = $this->products()->map(function (Product $product) use ($seo): array {
            $user = $product->vendor?->user;
            $price = is_numeric($product->price ?? null) ? number_format((float) $product->price, 2, '.', '') : null;

            return [
                'title' => trim((string) $product->title),
                'url' => $seo->canonicalProductUrl($product),
                'summary' => trim((string) ($product->summary ?? '')),
                'price' => $price,
                'category_slug' => Str::slug((string) ($product->category?->name ?? '')),
                'need_slugs' => collect((array) ($product->by_need ?? []))->map(fn ($slug): string => trim((string) $slug))->filter()->values()->all(),
                'search_text' => Str::lower(trim(implode(' ', array_filter([
                    $product->title,
                    $product->summary,
                    $product->tags_list,
                ])))),
                'updated_at' => $product->updated_at ? $product->updated_at->getTimestamp() : 0,
                'user' => $user,
                'practitioner' => $user?->public_display_name ?? $user?->full_name ?? $user?->name ?? null,
            ];
        });

        $offerings = $this->offerings()->map(function (OfferingV3 $offering) use ($seo): array {
            $user = $offering->vendor?->user;
            $price = is_numeric($offering->price ?? null) ? number_format((float) $offering->price, 2, '.', '') : null;

            return [
                'title' => trim((string) $offering->title),
                'url' => $seo->canonicalOfferingUrl($offering),
                'summary' => trim((string) ($offering->summary ?? '')),
                'price' => $price,
                'category_slug' => Str::slug((string) ($offering->category?->name ?? '')),
                'need_slugs' => [],
                'search_text' => Str::lower(trim(implode(' ', array_filter([
                    $offering->title,
                    $offering->summary,
                ])))),
                'updated_at' => $offering->updated_at ? $offering->updated_at->getTimestamp() : 0,
                'user' => $user,
                'practitioner' => $user?->public_display_name ?? $user?->full_name ?? $user?->name ?? null,
            ];
        });

        return $this->combinedOfferings = $products
            ->merge($offerings)
            ->filter(fn (array $item): bool => filled($item['url']) && filled($item['title']))
            ->values();
    }

    private function featuredOfferingCards(?string $format = null, ?array $definition = null): Collection
    {
        $cards = $definition !== null
            ? collect($this->offeringCards($definition))
            : $this->combinedOfferings()
                ->when($format !== null, function (Collection $collection) use ($format): Collection {
                    return $collection->filter(function (array $item) use ($format): bool {
                        $path = parse_url($item['url'], PHP_URL_PATH) ?: '';
                        return str_starts_with($path, '/' . trim($format, '/') . '/');
                    });
                })
                ->take(6)
                ->map(function (array $item): array {
                    return [
                        'title' => $item['title'],
                        'url' => $item['url'],
                        'summary' => $item['summary'],
                        'price' => $item['price'],
                        'service_type' => 'Wellness offering',
                        'practitioner' => $item['practitioner'],
                    ];
                });

        return $cards->values();
    }

    private function safetyNote(array $tags, ?array $need): string
    {
        $notes = [
            'Complementary wellbeing practices should not replace medical advice, diagnosis or treatment. If you are dealing with ongoing pain, anxiety, low mood, trauma symptoms or a medical condition, speak to a qualified healthcare professional.',
        ];

        if (in_array('pain', $tags, true) && $need !== null && filled($need['medical'])) {
            $notes[] = $need['medical'];
        }

        if (in_array('breathwork', $tags, true)) {
            $notes[] = 'Some breathwork practices may not be suitable during pregnancy, for people with certain heart, respiratory or mental health conditions, or for those with a history of seizures. Check suitability with the practitioner and seek professional advice if unsure.';
        }

        if (in_array('movement', $tags, true)) {
            $notes[] = 'Move within a comfortable range and stop if pain worsens. If you have an injury or medical condition, seek advice before starting a new movement practice.';
        }

        return implode(' ', array_unique($notes));
    }

    private function breadcrumbsForRecord(array $record): array
    {
        $definition = $record['modality'];
        return [
            ['label' => 'Home', 'url' => url('/')],
            ['label' => Str::headline($definition['format']), 'url' => $this->seo()->formatPageUrl($definition['format'])],
            ['label' => $this->taxonomyLabel($definition), 'url' => $this->seo()->modalityPageUrl($definition['format'], $definition['route_modality'])],
            ['label' => 'Guides', 'url' => $this->seo()->modalityGuidesUrl($definition['format'], $definition['route_modality'])],
            ['label' => $record['title'], 'url' => $this->absoluteGuideUrl($record)],
        ];
    }

    private function absoluteGuideUrl(array $record): string
    {
        return $this->seo()->guideUrl($record['format'], $record['route_modality'], $record['slug']);
    }

    private function relativeGuidePath(array $record): string
    {
        return '/' . trim($record['format'], '/') . '/' . trim($record['route_modality'], '/') . '/guides/' . trim($record['slug'], '/');
    }

    private function recordsForModality(string $format, string $modality): array
    {
        return collect($this->publishedRecords())
            ->filter(fn (array $record): bool => $record['format'] === $format && $record['route_modality'] === Str::slug($modality))
            ->values()
            ->all();
    }

    private function recordsForFormat(string $format): array
    {
        return collect($this->publishedRecords())
            ->filter(fn (array $record): bool => $record['format'] === $format)
            ->values()
            ->all();
    }

    private function supportedModalities(): array
    {
        return collect(self::MODALITIES)
            ->filter(fn (array $definition): bool => array_key_exists($definition['route_modality'], $this->taxonomyBySlug()))
            ->map(function (array $definition): array {
                return $definition;
            })
            ->values()
            ->all();
    }

    private function publishedFormats(): array
    {
        return collect($this->supportedModalities())
            ->pluck('format')
            ->unique()
            ->values()
            ->all();
    }

    private function publishedRecords(): array
    {
        if ($this->records !== null) {
            return $this->records;
        }

        $records = $this->customPublishedRecords();
        foreach ($this->supportedModalities() as $definition) {
            $records[] = [
                'guide_type' => 'what_is',
                'format' => $definition['format'],
                'route_modality' => $definition['route_modality'],
                'guide_slug_base' => $definition['guide_slug_base'],
                'slug' => 'what-is-' . $definition['guide_slug_base'],
                'title' => 'What Is ' . $definition['label'] . '?',
                'summary' => 'Understand what ' . $definition['label'] . ' is, what a ' . $definition['session_noun'] . ' may involve and how to browse trusted options.',
                'modality' => $definition,
            ];

            foreach ($definition['need_guides'] as $needKey) {
                $need = self::NEEDS[$needKey] ?? null;
                if ($need === null) {
                    continue;
                }

                $records[] = [
                    'guide_type' => 'modality_for_need',
                    'format' => $definition['format'],
                    'route_modality' => $definition['route_modality'],
                    'guide_slug_base' => $definition['guide_slug_base'],
                    'slug' => $definition['guide_slug_base'] . '-for-' . $need['slug'],
                    'need_key' => $need['slug'],
                    'need_slug' => $need['slug'],
                    'title' => 'How Can ' . $definition['label'] . ' Help with ' . $need['title'] . '?',
                    'summary' => 'Explore how ' . $definition['label'] . ' may support ' . strtolower($need['title']) . ' and what to expect from a session.',
                    'modality' => $definition,
                ];
            }

            if (!empty($definition['expect_slug']) && !empty($definition['expect_title'])) {
                $records[] = [
                    'guide_type' => 'what_to_expect',
                    'format' => $definition['format'],
                    'route_modality' => $definition['route_modality'],
                    'guide_slug_base' => $definition['guide_slug_base'],
                    'slug' => $definition['expect_slug'],
                    'title' => $definition['expect_title'],
                    'summary' => 'Learn how a typical ' . strtolower($definition['label']) . ' ' . $definition['session_noun'] . ' may flow and what to expect before booking.',
                    'modality' => $definition,
                ];
            }
        }

        $this->records = array_values($records);

        return $this->records = collect($records)
            ->filter(fn (array $record): bool => $this->passesValidation($this->buildGuidePage($record)))
            ->values()
            ->all();
    }

    private function customPublishedRecords(): array
    {
        if (! Schema::hasTable('guide_pages')) {
            return [];
        }

        return GuidePage::query()
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->orderBy('id')
            ->get()
            ->map(function (GuidePage $guide): array {
                $modality = Str::slug((string) $guide->modality);
                $format = $this->seo()->canonicalFormatKey((string) $guide->format);
                $definition = collect(self::MODALITIES)->first(fn (array $item): bool => $item['format'] === $format && $item['route_modality'] === $modality);
                $definition ??= [
                    'label' => Str::headline($modality),
                    'route_modality' => $modality,
                    'guide_slug_base' => $modality,
                    'format' => $format,
                    'summary' => $guide->summary,
                    'origin' => '',
                    'session' => 'The exact format varies by practitioner and should be explained before booking.',
                    'uses' => $guide->summary,
                    'beginner' => 'Ask the practitioner whether this format is suitable for your experience and circumstances.',
                    'chooser' => 'Look for a clear description, sensible suitability information and a practitioner whose approach feels grounded.',
                    'need_guides' => [],
                    'related_modalities' => [],
                    'safety_tags' => ['general'],
                    'session_noun' => 'session',
                    'session_plural' => 'sessions',
                    'expect_title' => 'What to Expect',
                    'expect_slug' => null,
                ];

                return [
                    'guide_type' => 'custom',
                    'format' => $format,
                    'route_modality' => $modality,
                    'guide_slug_base' => $definition['guide_slug_base'],
                    'slug' => Str::slug((string) $guide->slug),
                    'title' => $guide->title,
                    'summary' => $guide->summary,
                    'intro' => $guide->intro,
                    'quick_answer' => $guide->quick_answer,
                    'sections' => (array) $guide->sections,
                    'faqs' => (array) ($guide->faqs ?? []),
                    'safety_note' => $guide->safety_note,
                    'seo_title' => $guide->seo_title,
                    'seo_description' => $guide->seo_description,
                    'published_at' => optional($guide->published_at)->toAtomString() ?: self::PUBLISHED_AT,
                    'updated_at' => optional($guide->updated_at)->toAtomString() ?: self::PUBLISHED_AT,
                    'modality' => $definition,
                ];
            })
            ->filter(fn (array $record): bool => filled($record['slug']) && filled($record['route_modality']))
            ->values()
            ->all();
    }

    private function passesValidation(array $page): bool
    {
        if ($page === []) {
            return false;
        }

        if (!filled($page['h1'] ?? null) || !filled(data_get($page, 'seo.title')) || !filled(data_get($page, 'seo.description')) || !filled(data_get($page, 'seo.canonical'))) {
            return false;
        }

        if (!filled($page['intro'] ?? null) || !filled($page['quick_answer'] ?? null)) {
            return false;
        }

        if (count((array) ($page['sections'] ?? [])) < 5) {
            return false;
        }

        if (count((array) ($page['faqs'] ?? [])) < 3) {
            return false;
        }

        if (!filled($page['safety_note'] ?? null)) {
            return false;
        }

        if (($page['offerings'] ?? []) === [] && ($page['nearby_links'] ?? []) === [] && ($page['online_links'] ?? []) === []) {
            return false;
        }

        $fullText = Str::lower(trim(implode(' ', array_filter([
            $page['h1'] ?? '',
            $page['intro'] ?? '',
            $page['quick_answer'] ?? '',
            collect((array) ($page['sections'] ?? []))->flatMap(fn (array $section): array => $section['paragraphs'] ?? [])->implode(' '),
        ]))));

        foreach (['cures', 'guarantees', 'fixes', 'reverses', 'clinically proven', 'doctor-recommended', 'replaces medication'] as $banned) {
            if (str_contains($fullText, $banned)) {
                return false;
            }
        }

        return true;
    }

    private function taxonomyBySlug(): array
    {
        if ($this->taxonomyBySlug !== null) {
            return $this->taxonomyBySlug;
        }

        $items = [];
        foreach ((array) data_get(app(WhatCategoryCacheService::class)->load(), 'categories', []) as $category) {
            $slug = Str::slug((string) ($category['slug'] ?? ''));
            if ($slug === '') {
                continue;
            }

            $items[$slug] = $category;
        }

        return $this->taxonomyBySlug = $items;
    }

    private function taxonomyLabel(array $definition): string
    {
        return (string) data_get($this->taxonomyBySlug(), $definition['route_modality'] . '.title', $definition['label']);
    }

    private function products(): Collection
    {
        if ($this->products !== null) {
            return $this->products;
        }

        return $this->products = Product::query()
            ->select(['id', 'title', 'summary', 'price', 'handle', 'updated_at', 'category_id', 'vendor_id', 'by_need', 'tags_list', 'product_status_id', 'product_type'])
            ->with(['category:id,name', 'vendor.user:id,first_name,last_name,name,is_vendor'])
            ->where(function ($query): void {
                $query->whereHas('status', function ($status): void {
                    $status->whereIn('status', ['live', 'approved']);
                })->orWhereNull('product_status_id');
            })
            ->get();
    }

    private function offerings(): Collection
    {
        if ($this->offerings !== null) {
            return $this->offerings;
        }

        return $this->offerings = OfferingV3::query()
            ->select(['id', 'title', 'summary', 'price', 'slug', 'updated_at', 'category_id', 'vendor_id', 'status', 'type_id'])
            ->with(['category:id,name', 'vendor.user:id,first_name,last_name,name,is_vendor'])
            ->whereIn('status', ['live', 'approved'])
            ->get();
    }

    private function seo(): SeoStructureService
    {
        return app(SeoStructureService::class);
    }

    private function findModalityByRoute(string $format, string $modality): ?array
    {
        $format = $this->seo()->canonicalFormatKey($format);
        $modality = Str::slug($modality);

        return collect($this->supportedModalities())
            ->first(fn (array $definition): bool => $definition['format'] === $format && $definition['route_modality'] === $modality);
    }

    private function pushEntry(array &$entries, string $url, string $lastmod): void
    {
        $entries[$url] = [
            'loc' => $url,
            'lastmod' => Carbon::parse($lastmod)->toAtomString(),
        ];
    }

    private function textContainsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if ($needle !== '' && str_contains($haystack, Str::lower($needle))) {
                return true;
            }
        }

        return false;
    }
}
