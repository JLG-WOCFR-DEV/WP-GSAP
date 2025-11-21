<?php
/**
 * Plugin Name: WP Simple GSAP Block
 * Description: Version Dimension : Spline 3D, Hover Image Reveal & Magnetic Buttons.
 * Version: 18.0
 * Author: Gemini
 * License: GPLv2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_GSAP_Block_Plugin {

    public function __construct() {
        add_action('init', array($this, 'register_gsap_block'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('wp_head', array($this, 'add_custom_css'));
        add_action('wp_footer', array($this, 'add_cursor_markup'));
        add_action('wp_footer', array($this, 'add_liquid_svg_filter'));
        add_action('wp_footer', array($this, 'add_spline_script')); // V18 Spline Loader
    }

    public function register_gsap_block() {
        register_block_type('gemini/gsap-animator', array(
            'api_version' => 2,
            'title'       => 'GSAP Dimension',
            'category'    => 'design',
            'icon'        => 'admin-customizer',
            'description' => 'Spline 3D, Image Reveal, Rive & Physics.',
            'supports'    => array('html' => false, 'anchor' => true, 'align' => array('full', 'wide')),
            'attributes'  => array(
                // Base
                'animationType' => array('type' => 'string', 'default' => 'fadeUp'),
                'duration'      => array('type' => 'number', 'default' => 1),
                'delay'         => array('type' => 'number', 'default' => 0),
                'distance'      => array('type' => 'number', 'default' => 50),
                'ease'          => array('type' => 'string', 'default' => 'power2.out'),
                'scrub'         => array('type' => 'boolean', 'default' => false),
                
                // Legacy V1-V17
                'splitMode'     => array('type' => 'string', 'default' => 'none'),
                'stagger'       => array('type' => 'number', 'default' => 0),
                'hoverTextColor'=> array('type' => 'string', 'default' => ''),
                'videoScrub'    => array('type' => 'boolean', 'default' => false),
                'svgDraw'       => array('type' => 'boolean', 'default' => false),
                'enableSmoothScroll' => array('type' => 'boolean', 'default' => false),
                'enableCursor'       => array('type' => 'boolean', 'default' => false),
                'isMarquee'          => array('type' => 'boolean', 'default' => false),
                'marqueeSpeed'       => array('type' => 'number', 'default' => 10),
                'bodyColor'     => array('type' => 'string', 'default' => ''),
                'shouldPin'     => array('type' => 'boolean', 'default' => false),
                'textGradient'  => array('type' => 'string', 'default' => ''),
                'hasWavyLine'   => array('type' => 'boolean', 'default' => false),
                'enableTilt'    => array('type' => 'boolean', 'default' => false),
                'tiltIntensity' => array('type' => 'number', 'default' => 15),
                'enableGrain'   => array('type' => 'boolean', 'default' => false),
                'enableSkew'    => array('type' => 'boolean', 'default' => false),
                'enableScramble'=> array('type' => 'boolean', 'default' => false),
                'lottieUrl'     => array('type' => 'string', 'default' => ''),
                'enableConfetti'=> array('type' => 'boolean', 'default' => false),
                'enablePhysics' => array('type' => 'boolean', 'default' => false),
                'physicsGravity'=> array('type' => 'number', 'default' => 1),
                'enableLiquid'  => array('type' => 'boolean', 'default' => false),
                'liquidIntensity'=> array('type' => 'number', 'default' => 20),
                'enableCurtain' => array('type' => 'boolean', 'default' => false),
                'curtainColor'  => array('type' => 'string', 'default' => '#000000'),
                'parallaxScale' => array('type' => 'boolean', 'default' => false),
                'enableBlend'   => array('type' => 'boolean', 'default' => false),
                'riveUrl'       => array('type' => 'string', 'default' => ''),
                'riveStateMachine' => array('type' => 'string', 'default' => 'State Machine 1'),
                'enableLookAt'  => array('type' => 'boolean', 'default' => false),

                // NOUVEAUTES V18 (Dimension)
                'splineUrl'     => array('type' => 'string', 'default' => ''), // URL scène Spline (.splinecode)
                'hoverRevealImg'=> array('type' => 'string', 'default' => ''), // URL image au survol
                'magnetStrength'=> array('type' => 'number', 'default' => 0), // Force magnétique (Restored)
            ),
        ));
    }

    public function add_custom_css() {
        echo '<style>
            /* --- V18 Helpers --- */
            .gsap-hover-reveal { position: fixed; top: 0; left: 0; width: 200px; height: auto; pointer-events: none; z-index: 999; opacity: 0; transform: translate(-50%, -50%) scale(0.8); border-radius: 8px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); object-fit: cover; }
            
            /* --- Legacy --- */
            .gsap-rive-canvas { width: 100%; height: 100%; min-height: 300px; display: block; }
            .gsap-animator-wrapper { position: relative; }
            .gsap-curtain-layer { position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 10; pointer-events: none; transform-origin: top; }
            .gsap-blend-mode { mix-blend-mode: difference; color: white; }
            .gsap-physics-world { position: relative; overflow: hidden; touch-action: none; }
            .gsap-physics-item { position: absolute; top:0; left:0; will-change: transform; user-select: none; cursor: grab; }
            .gsap-physics-item:active { cursor: grabbing; }
            .gsap-liquid-fx { filter: url(#gsap-liquid-filter); }
            .gsap-lottie-container { width: 100%; height: 100%; min-height: 200px; }
            .gsap-fx-grain { position: relative; overflow: hidden; }
            .gsap-fx-grain::after { content: ""; position: absolute; top: -50%; left: -50%; width: 200%; height: 200%; background-image: url("data:image/svg+xml,%3Csvg viewBox=\'0 0 200 200\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cfilter id=\'noiseFilter\'%3E%3CfeTurbulence type=\'fractalNoise\' baseFrequency=\'0.65\' numOctaves=\'3\' stitchTiles=\'stitch\'/%3E%3C/filter%3E%3Crect width=\'100%25\' height=\'100%25\' filter=\'url(%23noiseFilter)\' opacity=\'0.15\'/%3E%3C/svg%3E"); pointer-events: none; z-index: 10; opacity: 0.4; animation: grainAnim 0.5s steps(5) infinite; mix-blend-mode: overlay; }
            @keyframes grainAnim { 0% { transform: translate(0,0); } 100% { transform: translate(5%,0); } }
            .gsap-fx-tilt { transform-style: preserve-3d; will-change: transform; transition: transform 0.1s linear; }
            .gsap-fx-gradient { background-image: var(--gsap-gradient, linear-gradient(45deg, #333, #333)); background-size: 200% auto; background-clip: text; -webkit-background-clip: text; -webkit-text-fill-color: transparent; color: transparent; transition: background-position 0.5s ease; }
            .gsap-fx-gradient:hover { background-position: right center; }
            .gsap-fx-wave { position: relative; display: inline-block; text-decoration: none; }
            .gsap-fx-wave::after { content: ""; position: absolute; left: 0; bottom: -0.1em; width: 100%; height: 0.4em; background-image: var(--gsap-gradient, currentColor); background-size: 200% auto; -webkit-mask: url("data:image/svg+xml,%3Csvg width=\'40\' height=\'12\' viewBox=\'0 0 40 12\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cpath d=\'M0 6 Q 10 12 20 6 T 40 6\' fill=\'none\' stroke=\'black\' stroke-width=\'3\' stroke-linecap=\'round\' /%3E%3C/svg%3E") repeat-x 0 0; mask: url("data:image/svg+xml,%3Csvg width=\'40\' height=\'12\' viewBox=\'0 0 40 12\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cpath d=\'M0 6 Q 10 12 20 6 T 40 6\' fill=\'none\' stroke=\'black\' stroke-width=\'3\' stroke-linecap=\'round\' /%3E%3C/svg%3E") repeat-x 0 0; -webkit-mask-size: 20px 12px; mask-size: 20px 12px; animation: gsapWaveScroll 1s linear infinite; }
            @keyframes gsapWaveScroll { from { -webkit-mask-position: 0 0; mask-position: 0 0; } to { -webkit-mask-position: -40px 0; mask-position: -40px 0; } }
            .gsap-marquee-container { display: flex; overflow: hidden; white-space: nowrap; width: 100%; }
            .gsap-marquee-part { flex-shrink: 0; display: inline-block; padding-right: 20px; }
            body.has-custom-cursor { cursor: none; }
            body.has-custom-cursor a, body.has-custom-cursor button { cursor: none; }
            #gsap-custom-cursor { position: fixed; top: 0; left: 0; width: 20px; height: 20px; border: 2px solid currentColor; border-radius: 50%; pointer-events: none; z-index: 9999; transform: translate(-50%, -50%); transition: width 0.2s, height 0.2s, background-color 0.2s; mix-blend-mode: difference; color: white; }
            #gsap-custom-cursor.hovered { width: 50px; height: 50px; background-color: white; opacity: 0.5; border-color: transparent; }
            html.lenis { height: auto; } .lenis.lenis-smooth { scroll-behavior: auto; }
            .wp-block-gemini-gsap-animator span { display: inline-block; transform-style: preserve-3d; }
        </style>';
    }

    public function add_liquid_svg_filter() {
        echo '<svg style="position: absolute; width: 0; height: 0; pointer-events: none;"><defs><filter id="gsap-liquid-filter"><feTurbulence type="fractalNoise" baseFrequency="0.01 0.01" numOctaves="2" result="warp" /><feDisplacementMap id="gsap-liquid-displacement" xChannelSelector="R" yChannelSelector="G" scale="0" in="SourceGraphic" in2="warp" /></filter></defs></svg>';
    }

    public function add_spline_script() {
        echo '<script type="module" src="https://unpkg.com/@splinetool/viewer@1.0.54/build/spline-viewer.js"></script>';
    }

    public function add_cursor_markup() {
        echo '<div id="gsap-custom-cursor" style="display:none;"></div>';
    }

    public function enqueue_frontend_assets() {
        if (is_admin()) return;

        // Core
        wp_enqueue_script('gsap-core', 'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js', array(), '3.12.2', true);
        wp_enqueue_script('gsap-scrolltrigger', 'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js', array('gsap-core'), '3.12.2', true);
        wp_enqueue_script('lenis', 'https://cdn.jsdelivr.net/gh/studio-freight/lenis@1.0.29/bundled/lenis.min.js', array(), '1.0.29', true);
        
        // Libraries
        wp_enqueue_script('lottie', 'https://cdnjs.cloudflare.com/ajax/libs/lottie-web/5.12.2/lottie.min.js', array(), '5.12.2', true);
        wp_enqueue_script('confetti', 'https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.0/dist/confetti.browser.min.js', array(), '1.9.0', true);
        wp_enqueue_script('matter-js', 'https://cdnjs.cloudflare.com/ajax/libs/matter-js/0.19.0/matter.min.js', array(), '0.19.0', true);
        wp_enqueue_script('rive', 'https://unpkg.com/@rive-app/canvas@2.7.0', array(), '2.7.0', true);

        wp_register_script('gsap-master-init', false, array('gsap-scrolltrigger', 'lenis', 'lottie', 'confetti', 'matter-js', 'rive'), '18.0', true);

        $js_logic = "
        document.addEventListener('DOMContentLoaded', function() {
            gsap.registerPlugin(ScrollTrigger);
            
            // --- GLOBAL INIT ---
            const allBlocks = document.querySelectorAll('.wp-block-gemini-gsap-animator');
            let smoothActive=false, cursorActive=false;
            allBlocks.forEach(b => {
                if(b.getAttribute('data-gsap-smooth') === 'true') smoothActive = true;
                if(b.getAttribute('data-gsap-cursor') === 'true') cursorActive = true;
            });
            if(smoothActive && typeof Lenis !== 'undefined') {
                const lenis = new Lenis({ duration: 1.2, easing: (t) => Math.min(1, 1.001 - Math.pow(2, -10 * t)) });
                function raf(time) { lenis.raf(time); requestAnimationFrame(raf); }
                requestAnimationFrame(raf); lenis.on('scroll', ScrollTrigger.update);
                gsap.ticker.add((time)=>{ lenis.raf(time * 1000); }); gsap.ticker.lagSmoothing(0);
            }
            if(cursorActive) {
                const cursor = document.getElementById('gsap-custom-cursor');
                if(cursor) {
                    cursor.style.display = 'block'; document.body.classList.add('has-custom-cursor');
                    let mouseX=0, mouseY=0, cursorX=0, cursorY=0;
                    document.addEventListener('mousemove', (e) => { mouseX = e.clientX; mouseY = e.clientY; });
                    gsap.ticker.add(() => {
                        cursorX += (mouseX - cursorX) * 0.2; cursorY += (mouseY - cursorY) * 0.2;
                        cursor.style.transform = `translate3d(\${cursorX}px, \${cursorY}px, 0) translate(-50%, -50%)`;
                    });
                    document.querySelectorAll('a, button').forEach(l => {
                        l.addEventListener('mouseenter', () => cursor.classList.add('hovered'));
                        l.addEventListener('mouseleave', () => cursor.classList.remove('hovered'));
                    });
                }
            }

            // --- HELPERS ---
            function splitText(element, mode) { 
                if(mode === 'none') return [element];
                let target = element.querySelector('h1, h2, h3, h4, h5, h6, p, div') || element;
                const text = target.innerText; target.innerHTML = ''; let spans = [];
                text.split(mode === 'chars' ? '' : ' ').forEach((item, i, arr) => {
                   let s = document.createElement('span'); s.innerText = item; s.style.display = 'inline-block'; s.style.whiteSpace = 'pre';
                   if(mode === 'chars' && item === ' ') s.style.width = '0.3em'; if(mode === 'words' && i < arr.length - 1) target.appendChild(document.createTextNode(' '));
                   target.appendChild(s); spans.push(s);
                });
                return spans;
            }
            function scrambleText(element, duration) {
                const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789@#$%&'; let target = element.querySelector('h1, h2, h3, h4, h5, h6, p') || element;
                const originalText = target.innerText; const length = originalText.length; let obj = { val: 0 };
                gsap.to(obj, { val: 1, duration: duration || 1.5, ease: 'power2.inOut', onUpdate: () => {
                    const progress = obj.val; let result = '';
                    for(let i=0; i<length; i++) { if(originalText[i] === ' ') { result += ' '; continue; } result += (i < progress * length) ? originalText[i] : chars[Math.floor(Math.random() * chars.length)]; }
                    target.innerText = result;
                }});
            }

            // --- BLOCK LOOP ---
            allBlocks.forEach(function(block) {
                // Props
                const type = block.getAttribute('data-gsap-type') || 'fade';
                const duration = parseFloat(block.getAttribute('data-gsap-duration')) || 1;
                const delay = parseFloat(block.getAttribute('data-gsap-delay')) || 0;
                const ease = block.getAttribute('data-gsap-ease') || 'power2.out';
                const distance = parseFloat(block.getAttribute('data-gsap-distance')) || 0;
                const splitMode = block.getAttribute('data-gsap-split') || 'none';
                const stagger = parseFloat(block.getAttribute('data-gsap-stagger')) || 0;
                const scrub = block.getAttribute('data-gsap-scrub') === 'true';
                const hoverTextColor = block.getAttribute('data-gsap-hovercolor');
                const videoScrub = block.getAttribute('data-gsap-videoscrub') === 'true';
                const svgDraw = block.getAttribute('data-gsap-svgdraw') === 'true';
                const isMarquee = block.getAttribute('data-gsap-marquee') === 'true';
                const marqueeSpeed = parseFloat(block.getAttribute('data-gsap-marqueespeed')) || 10;
                const bodyColor = block.getAttribute('data-gsap-bodycolor');
                const shouldPin = block.getAttribute('data-gsap-pin') === 'true';
                const textGradient = block.getAttribute('data-gsap-textgradient');
                const hasWavyLine = block.getAttribute('data-gsap-wavy') === 'true';
                const enableTilt = block.getAttribute('data-gsap-tilt') === 'true';
                const tiltIntensity = parseFloat(block.getAttribute('data-gsap-tilt-intensity')) || 15;
                const enableGrain = block.getAttribute('data-gsap-grain') === 'true';
                const enableSkew = block.getAttribute('data-gsap-skew') === 'true';
                const enableScramble = block.getAttribute('data-gsap-scramble') === 'true';
                const lottieUrl = block.getAttribute('data-gsap-lottie');
                const enableConfetti = block.getAttribute('data-gsap-confetti') === 'true';
                const enablePhysics = block.getAttribute('data-gsap-physics') === 'true';
                const physicsGravity = parseFloat(block.getAttribute('data-gsap-physics-gravity')) || 1;
                const enableLiquid = block.getAttribute('data-gsap-liquid') === 'true';
                const liquidIntensity = parseFloat(block.getAttribute('data-gsap-liquid-intensity')) || 20;
                const enableCurtain = block.getAttribute('data-gsap-curtain') === 'true';
                const curtainColor = block.getAttribute('data-gsap-curtain-color') || '#000000';
                const parallaxScale = block.getAttribute('data-gsap-parallax-scale') === 'true';
                const enableBlend = block.getAttribute('data-gsap-blend') === 'true';
                const riveUrl = block.getAttribute('data-gsap-rive');
                const riveStateMachine = block.getAttribute('data-gsap-rivestate') || 'State Machine 1';
                const enableLookAt = block.getAttribute('data-gsap-lookat') === 'true';

                // V18 Props
                const splineUrl = block.getAttribute('data-gsap-spline');
                const hoverRevealImg = block.getAttribute('data-gsap-hoverimg');
                const magnetStrength = parseFloat(block.getAttribute('data-gsap-magnet')) || 0;

                // --- V18 LOGIC: SPLINE 3D ---
                if(splineUrl) {
                    // On injecte le tag <spline-viewer>
                    block.innerHTML = '';
                    const viewer = document.createElement('spline-viewer');
                    viewer.setAttribute('url', splineUrl);
                    // Optionnel: gérer events ou loading
                    block.appendChild(viewer);
                    return;
                }

                // --- V18 LOGIC: HOVER IMAGE REVEAL ---
                if(hoverRevealImg) {
                    // Création de l'image cachée
                    const img = document.createElement('img');
                    img.src = hoverRevealImg;
                    img.className = 'gsap-hover-reveal';
                    document.body.appendChild(img); // Append to body to float freely
                    
                    // QuickTo pour perf
                    const xTo = gsap.quickTo(img, 'x', {duration: 0.4, ease: 'power3'});
                    const yTo = gsap.quickTo(img, 'y', {duration: 0.4, ease: 'power3'});
                    
                    block.addEventListener('mouseenter', () => gsap.to(img, {autoAlpha: 1, scale: 1, duration: 0.3}));
                    block.addEventListener('mouseleave', () => gsap.to(img, {autoAlpha: 0, scale: 0.8, duration: 0.3}));
                    block.addEventListener('mousemove', (e) => {
                        xTo(e.clientX);
                        yTo(e.clientY);
                    });
                }

                // --- V18 LOGIC: MAGNET BUTTON ---
                if(magnetStrength > 0) {
                    block.addEventListener('mousemove', (e) => {
                        const rect = block.getBoundingClientRect();
                        const x = (e.clientX - rect.left - rect.width / 2) * (magnetStrength / 100);
                        const y = (e.clientY - rect.top - rect.height / 2) * (magnetStrength / 100);
                        gsap.to(block, { x: x, y: y, duration: 0.5, ease: 'power2.out' });
                    });
                    block.addEventListener('mouseleave', () => {
                        gsap.to(block, { x: 0, y: 0, duration: 1, ease: 'elastic.out(1, 0.3)' });
                    });
                }

                // --- V17 LOGIC: RIVE ---
                if(riveUrl && typeof rive !== 'undefined') {
                    block.innerHTML = ''; const canvas = document.createElement('canvas'); canvas.className = 'gsap-rive-canvas'; block.appendChild(canvas);
                    const r = new rive.Rive({ src: riveUrl, canvas: canvas, autoplay: true, stateMachines: riveStateMachine, onLoad: () => { r.resizeDrawingSurfaceToCanvas(); } });
                    return;
                }
                // --- V17 LOGIC: LOOK AT ---
                if(enableLookAt) {
                    const t = block; window.addEventListener('mousemove', (e) => { const r = t.getBoundingClientRect(); const cx = r.left + r.width/2; const cy = r.top + r.height/2; const angle = Math.atan2(e.clientY - cy, e.clientX - cx) * (180 / Math.PI); gsap.to(t, { rotation: angle, duration: 0.3, ease: 'power1.out' }); });
                }

                // --- LEGACY ---
                if(enableBlend) block.classList.add('gsap-blend-mode');
                if(enableCurtain) { const c=document.createElement('div'); c.className='gsap-curtain-layer'; c.style.backgroundColor=curtainColor; block.appendChild(c); gsap.set(block,{opacity:1}); ScrollTrigger.create({trigger:block,start:'top 85%',onEnter:()=>{gsap.to(c,{scaleY:0,transformOrigin:'top',duration:1.2,ease:'power3.inOut'});}}); }
                if(parallaxScale) { const m=block.querySelector('img,video,.wp-block-image img,.wp-block-cover'); if(m){ block.style.overflow='hidden'; gsap.fromTo(m,{scale:1.2},{scale:1,ease:'none',scrollTrigger:{trigger:block,start:'top bottom',end:'bottom top',scrub:true}}); } }
                if(enableLiquid) { const dm=document.querySelector('#gsap-liquid-displacement'); if(dm){ block.classList.add('gsap-liquid-fx'); block.addEventListener('mouseenter',()=>gsap.to(dm,{attr:{scale:liquidIntensity},duration:0.5})); block.addEventListener('mouseleave',()=>gsap.to(dm,{attr:{scale:0},duration:0.5})); } }
                if(enablePhysics && typeof Matter!=='undefined') { block.classList.add('gsap-physics-world'); const Engine=Matter.Engine,Render=Matter.Render,World=Matter.World,Bodies=Matter.Bodies,Mouse=Matter.Mouse,MouseConstraint=Matter.MouseConstraint; const engine=Engine.create(); engine.world.gravity.y=physicsGravity; const w=block.offsetWidth,h=block.offsetHeight||400; if(!block.offsetHeight)block.style.height='400px'; const ch=Array.from(block.children); const bo=[]; ch.forEach((c,i)=>{c.classList.add('gsap-physics-item');const bx=(w/ch.length)*i+50,by=-Math.random()*500-50;const body=Bodies.rectangle(bx,by,c.offsetWidth,c.offsetHeight,{restitution:0.8,friction:0.1});bo.push({body:body,elem:c});World.add(engine.world,body);}); const fl=Bodies.rectangle(w/2,h+30,w,60,{isStatic:true}),wl=Bodies.rectangle(-30,h/2,60,h,{isStatic:true}),wr=Bodies.rectangle(w+30,h/2,60,h,{isStatic:true}); World.add(engine.world,[fl,wl,wr]); const mo=Mouse.create(block); mo.element.removeEventListener('mousewheel',mo.mousewheel); mo.element.removeEventListener('DOMMouseScroll',mo.mousewheel); const mc=MouseConstraint.create(engine,{mouse:mo,constraint:{stiffness:0.2,render:{visible:false}}}); World.add(engine.world,mc); Matter.Runner.run(engine); gsap.ticker.add(()=>{bo.forEach(i=>{const{x,y}=i.body.position;const r=i.body.angle;i.elem.style.transform=`translate(\${x-i.elem.offsetWidth/2}px,\${y-i.elem.offsetHeight/2}px) rotate(\${r}rad)`;});}); return; }
                if(lottieUrl && typeof lottie!=='undefined') { block.innerHTML=''; const lc=document.createElement('div'); lc.className='gsap-lottie-container'; block.appendChild(lc); const anim=lottie.loadAnimation({container:lc,renderer:'svg',loop:!scrub,autoplay:!scrub,path:lottieUrl}); if(scrub){anim.addEventListener('DOMLoaded',function(){ScrollTrigger.create({trigger:block,start:'top bottom',end:'bottom top',scrub:1,onUpdate:self=>{const f=self.progress*(anim.totalFrames-1);anim.goToAndStop(f,true);}});});} }
                if(enableConfetti && typeof confetti!=='undefined') { ScrollTrigger.create({trigger:block,start:'top 80%',onEnter:()=>{const r=block.getBoundingClientRect(); confetti({particleCount:100,spread:70,origin:{x:(r.left+r.width/2)/window.innerWidth, y:(r.top+r.height/2)/window.innerHeight}});}}); block.addEventListener('mouseenter',()=>{const r=block.getBoundingClientRect(); confetti({particleCount:30,spread:50,origin:{x:(r.left+r.width/2)/window.innerWidth, y:(r.top+r.height/2)/window.innerHeight}, startVelocity:20});}); }
                if(enableSkew) { ScrollTrigger.create({ trigger:block,start:'top bottom',end:'bottom top',onUpdate:(self)=>{let s=self.getVelocity()/-300;s=Math.max(-15,Math.min(15,s));gsap.to(block,{skewY:s,duration:0.1,ease:'power3.out',overwrite:'auto'});} }); }
                if(enableScramble) { gsap.set(block,{opacity:0}); ScrollTrigger.create({trigger:block,start:'top 85%',onEnter:()=>{gsap.set(block,{opacity:1});scrambleText(block,duration);}}); return; }
                if(enableGrain) block.classList.add('gsap-fx-grain');
                if(enableTilt) { block.classList.add('gsap-fx-tilt'); gsap.set(block,{transformPerspective:1000}); block.addEventListener('mousemove',function(e){const r=block.getBoundingClientRect();const xp=(e.clientX-r.left)/r.width-0.5;const yp=(e.clientY-r.top)/r.height-0.5;gsap.to(block,{rotationY:xp*tiltIntensity,rotationX:-yp*tiltIntensity,duration:0.5,ease:'power1.out'});}); block.addEventListener('mouseleave',function(){gsap.to(block,{rotationY:0,rotationX:0,duration:1,ease:'elastic.out(1,0.5)'});}); }
                if(textGradient||hasWavyLine) { const it=block.querySelector('h1,h2,h3,h4,h5,h6,p')||block; if(textGradient){it.style.setProperty('--gsap-gradient',textGradient);it.classList.add('gsap-fx-gradient');} if(hasWavyLine){if(!textGradient)it.style.setProperty('--gsap-gradient','currentColor');it.classList.add('gsap-fx-wave');} }
                if(isMarquee) { const c=block.innerHTML; block.innerHTML=''; const co=document.createElement('div'); co.className='gsap-marquee-container'; for(let i=0;i<4;i++){const p=document.createElement('div');p.className='gsap-marquee-part';p.innerHTML=c;co.appendChild(p);} block.appendChild(co); gsap.to(co,{xPercent:-50,repeat:-1,duration:marqueeSpeed,ease:'linear'}); return; }
                if(videoScrub) { const v=block.querySelector('video'); if(v){v.preload='metadata';v.pause();const init=()=>{gsap.fromTo(v,{currentTime:0},{currentTime:v.duration||1,ease:'none',scrollTrigger:{trigger:block,start:'top bottom',end:'bottom top',scrub:1}})};if(v.readyState>=1)init();else v.onloadedmetadata=init;} return; }
                if(svgDraw) { const p=block.querySelectorAll('path'); if(p.length){block.classList.add('gsap-svg-draw'); p.forEach(i=>{const l=i.getTotalLength();i.style.strokeDasharray=l;i.style.strokeDashoffset=l;gsap.to(i,{strokeDashoffset:0,duration:duration,ease:ease,scrollTrigger:{trigger:block,start:'top 80%',scrub:scrub?1:false}});}); gsap.set(block,{opacity:1});} return; }

                // --- STANDARD ---
                if(lottieUrl || isMarquee || videoScrub || enableScramble || enablePhysics || enableCurtain || riveUrl || splineUrl) return;
                let effectiveSplit = splitMode; if(type === 'charsSpin3D' && splitMode === 'none') effectiveSplit = 'chars';
                let targets = block; if (effectiveSplit !== 'none') { targets = splitText(block, effectiveSplit); gsap.set(block, { opacity: 1, perspective: 1000 }); } else if (stagger > 0 && block.children.length > 0) { targets = block.children; gsap.set(block, { opacity: 1 }); }
                let initialProps = { opacity: 0, duration: duration, delay: delay, ease: ease };
                switch(type) { case 'fadeUp': initialProps.y = distance || 50; break; case 'fadeDown': initialProps.y = -(distance || 50); break; case 'zoomIn': initialProps.scale = 0.5; break; case 'charsSpin3D': initialProps.opacity = 0; initialProps.rotateY = 90; initialProps.y = 20; if(stagger===0) initialProps.stagger=0.05; break; }
                if (!initialProps.stagger && (stagger > 0 || effectiveSplit !== 'none') && targets.length > 1) initialProps.stagger = stagger || 0.05;
                let tConfig = { trigger: block, start: 'top 85%' }; if (scrub) { tConfig.scrub = 1; tConfig.start = 'top bottom'; tConfig.end = 'top center'; } if (shouldPin) { tConfig.pin = true; tConfig.scrub = true; tConfig.start = 'top center'; tConfig.end = '+=100%'; }
                initialProps.scrollTrigger = tConfig; if(!shouldPin) gsap.from(targets, initialProps);
                if (bodyColor) { const orig = getComputedStyle(document.body).backgroundColor; ScrollTrigger.create({ trigger: block, start: 'top 60%', end: 'bottom 40%', onEnter: ()=>gsap.to('body',{backgroundColor:bodyColor}), onEnterBack:()=>gsap.to('body',{backgroundColor:bodyColor}), onLeaveBack:()=>gsap.to('body',{backgroundColor:orig}) }); }
                if (hoverTextColor && targets.length) { block.addEventListener('mouseenter', ()=>gsap.to(targets,{color:hoverTextColor,duration:0.5,stagger:0.02})); block.addEventListener('mouseleave', ()=>gsap.to(targets,{color:'inherit',duration:0.5,stagger:0.02})); }
            });
        });
        ";

        wp_add_inline_script('gsap-master-init', $js_logic);
        wp_enqueue_script('gsap-master-init');
    }
}

new WP_GSAP_Block_Plugin();
