window.onerror = function(message, source, lineno, colno, error) {
    alert("Script Error: " + message + "\nLine: " + lineno + "\nSource: " + source);
    return false;
};

function initializeApp() {
    function logDebug(msg, isError = false) {
        if (isError) {
            console.error(msg);
        } else {
            console.log(msg);
        }
    }

    // Default State
    const state = {
        driver: {
            name: "",
            phone: "",
            vehicleType: "bike",
            plateNumber: "",
            lat: 22.7735, // Centered at Telco Colony
            lng: 86.2505
        },
        earnings: {
            today: 0,
            rides: 0,
            onlineTime: 0, // In hours
            acceptanceRate: 100
        },
        weeklyEarnings: {
            mon: 350,
            tue: 680,
            wed: 190,
            thu: 920,
            fri: 0, // Today
            sat: 0,
            sun: 0
        },
        history: [],
        activeRequest: null,
        activeRide: null,
        map: null,
        markers: {
            driver: null,
            pickup: null,
            dropoff: null
        },
        routePolyline: null,
        status: "offline", // offline, online
        requestTimeout: null,
        requestInterval: null,
        simulationInterval: null,
        onlineTimeInterval: null
    };

    try {
        logDebug("Initializing onboarding flows...");
        initOnboardingFlow();
        logDebug("Initializing sidebar routing...");
        initSidebarNavigation();
        logDebug("Initialization complete!");
    } catch (err) {
        console.error("App Init Error:", err);
        alert("App Init Error: " + err.message);
    }

    function switchScreen(screenId) {
        document.querySelectorAll('.screen').forEach(s => s.classList.remove('active'));
        document.getElementById(screenId).classList.add('active');
    }

    function showPanel(panelId) {
        document.querySelectorAll('.booking-panel').forEach(p => p.classList.remove('active-panel'));
        document.getElementById(panelId).classList.add('active-panel');
    }

    /* ==========================================================================
       ONBOARDING REGISTRATION & OTP VERIFICATION
       ========================================================================== */
    function initOnboardingFlow() {
        const welcomeRegisterBtn = document.getElementById('btn-welcome-register');
        
        const loginNameInput = document.getElementById('login-name');
        const loginPhoneInput = document.getElementById('login-phone');
        const loginVehicleSelect = document.getElementById('login-vehicle');
        const loginPlateInput = document.getElementById('login-plate');
        const loginNextBtn = document.getElementById('btn-login-next');
        const backToWelcomeBtn = document.getElementById('btn-back-to-welcome');
        
        const otpInputs = document.querySelectorAll('.otp-input');
        const otpErrorMsg = document.getElementById('otp-error-msg');
        const verifyOtpBtn = document.getElementById('btn-verify-otp');
        const backToLoginBtn = document.getElementById('btn-back-to-login');

        // Transition from Welcome Screen
        welcomeRegisterBtn.addEventListener('click', () => switchScreen('screen-login'));
        backToWelcomeBtn.addEventListener('click', () => switchScreen('screen-welcome'));

        // Input validation for Registration Screen
        const validateLoginFields = () => {
            const nameVal = loginNameInput.value.trim();
            const phoneVal = loginPhoneInput.value.replace(/\D/g, '');
            const plateVal = loginPlateInput.value.trim();
            loginPhoneInput.value = phoneVal;
            
            loginNextBtn.disabled = nameVal.length < 2 || phoneVal.length !== 10 || plateVal.length < 4;
        };

        loginNameInput.addEventListener('input', validateLoginFields);
        loginPhoneInput.addEventListener('input', validateLoginFields);
        loginPlateInput.addEventListener('input', validateLoginFields);

        // Transition to OTP Screen
        loginNextBtn.addEventListener('click', () => {
            const nameVal = loginNameInput.value.trim();
            const phoneVal = loginPhoneInput.value;
            
            document.getElementById('otp-display-name').textContent = nameVal;
            document.getElementById('otp-display-phone').textContent = `+91 ${phoneVal}`;
            
            // Reset OTP credentials
            otpErrorMsg.classList.add('hidden');
            otpInputs.forEach(input => input.value = '');
            
            switchScreen('screen-otp');
            setTimeout(() => otpInputs[0].focus(), 400);
        });

        backToLoginBtn.addEventListener('click', () => switchScreen('screen-login'));

        // OTP inputs chaining
        otpInputs.forEach((input, index) => {
            input.addEventListener('input', () => {
                if (input.value && index < otpInputs.length - 1) {
                    otpInputs[index + 1].focus();
                }
            });
            input.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && !input.value && index > 0) {
                    otpInputs[index - 1].focus();
                }
            });
        });

        // Helper to verify OTP code
        const handleOtpSubmit = () => {
            try {
                let otpCode = '';
                otpInputs.forEach(input => otpCode += input.value);

                if (otpCode === "1234") {
                    // Correct OTP -> populate state & load main screen
                    otpErrorMsg.classList.add('hidden');
                    
                    state.driver.name = loginNameInput.value.trim();
                    state.driver.phone = `+91 ${loginPhoneInput.value}`;
                    state.driver.vehicleType = loginVehicleSelect.value;
                    state.driver.plateNumber = loginPlateInput.value.toUpperCase();

                    // Format vehicle display name
                    const vehicleLabels = {
                        bike: "Qwikk Bike",
                        scooty: "Scooty",
                        auto: "Auto",
                        'bike-pink': "Pink Bike",
                        'cab-economy': "Cab Economy",
                        'cab-priority': "Cab Priority",
                        'cab-premium': "Cab Premium",
                        'cab-xl': "Cab XL"
                    };
                    const vehicleDisplay = `${vehicleLabels[state.driver.vehicleType]} &bull; ${state.driver.plateNumber}`;

                    document.getElementById('sidebar-captain-name').textContent = state.driver.name;
                    document.getElementById('sidebar-captain-vehicle').innerHTML = vehicleDisplay;
                    
                    // Initialise Leaflet Map
                    switchScreen('screen-app');
                    initLeafletMap();
                } else {
                    // Incorrect OTP -> show error, clear inputs
                    otpErrorMsg.classList.remove('hidden');
                    otpInputs.forEach(input => input.value = '');
                    otpInputs[0].focus();
                }
            } catch (err) {
                console.error("OTP Verification Error:", err);
                alert("Verification Error: " + err.message);
            }
        };

        // Verify OTP constraint
        verifyOtpBtn.addEventListener('click', handleOtpSubmit);

        // Allow pressing Enter on the final input to submit
        otpInputs.forEach((input, index) => {
            input.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    handleOtpSubmit();
                }
            });
        });
    }

    /* ==========================================================================
       SIDEBAR & VIEW ROUTING
       ========================================================================== */
    function initSidebarNavigation() {
        const menuItems = document.querySelectorAll('.menu-item');
        const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
        const sidebar = document.querySelector('.sidebar');
        const logoutBtn = document.getElementById('btn-logout');

        menuItems.forEach(item => {
            item.addEventListener('click', () => {
                const target = item.getAttribute('data-target');
                menuItems.forEach(mi => mi.classList.remove('active'));
                item.classList.add('active');

                showPanel('panel-' + target);

                if (window.innerWidth <= 900) {
                    sidebar.classList.remove('open');
                }
            });
        });

        document.querySelectorAll('.btn-close-subpanel').forEach(btn => {
            btn.addEventListener('click', () => {
                showPanel('panel-dashboard');
                menuItems.forEach(mi => mi.classList.remove('active'));
                document.querySelector('.menu-item[data-target="dashboard"]').classList.add('active');
            });
        });

        mobileMenuToggle.addEventListener('click', () => {
            sidebar.classList.toggle('open');
        });

        logoutBtn.addEventListener('click', () => {
            // Turn off online status first, clean intervals
            toggleOnlineStatus(false);
            window.location.reload();
        });

        // Withdraw payouts button listener
        document.getElementById('btn-withdraw-payout').addEventListener('click', () => {
            if (state.earnings.today === 0 && state.weeklyEarnings.fri === 0) {
                alert("You don't have any earnings to withdraw today!");
                return;
            }
            alert(`Bank payout of ₹${state.earnings.today + state.weeklyEarnings.fri} requested successfully! Payout will reflect in your linked SBI account within 24 hours.`);
        });
    }

    /* ==========================================================================
       LEAFLET MAP INITIALISATION
       ========================================================================== */
    function initLeafletMap() {
        if (state.map) return;
        
        logDebug("Initializing Leaflet map...");
        state.map = L.map('leaflet-map-container', {
            zoomControl: false,
            attributionControl: false
        }).setView([state.driver.lat, state.driver.lng], 14);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19
        }).addTo(state.map);

        L.control.zoom({ position: 'bottomright' }).addTo(state.map);

        // Position driver icon
        updateDriverMarkerOnMap();

        // Bind online switch toggle listener
        const toggleBtn = document.getElementById('toggle-status-btn');
        toggleBtn.addEventListener('change', (e) => {
            toggleOnlineStatus(e.target.checked);
        });

        // Payout withdraw display sync
        updateEarningsDisplay();
    }

    function updateDriverMarkerOnMap() {
        if (!state.map) return;

        if (state.markers.driver) {
            state.map.removeLayer(state.markers.driver);
        }

        const isNavMode = state.activeRide !== null;

        const driverIcon = L.divIcon({
            html: `<div class="driver-marker-pulse ${isNavMode ? 'nav-pulse' : ''}"><i data-lucide="${['bike', 'scooty', 'bike-pink'].includes(state.driver.vehicleType) ? 'bike' : 'car'}"></i></div>`,
            className: 'custom-div-icon',
            iconSize: [36, 36],
            iconAnchor: [18, 18]
        });

        state.markers.driver = L.marker([state.driver.lat, state.driver.lng], { icon: driverIcon }).addTo(state.map);
        if (window.lucide && typeof lucide.createIcons === 'function') {
            lucide.createIcons();
        }
    }

    /* ==========================================================================
       ONLINE / OFFLINE STATUS TOGGLE & LOOP
       ========================================================================== */
    function toggleOnlineStatus(isOnline) {
        // Sync checkbox status
        const toggleBtn = document.getElementById('toggle-status-btn');
        if (toggleBtn) toggleBtn.checked = isOnline;

        // Sync header status pill
        const pill = document.getElementById('header-status-pill');

        if (isOnline) {
            state.status = "online";
            document.getElementById('txt-status-header').textContent = "Status: Online";
            document.getElementById('txt-status-desc').textContent = "Searching for passengers nearby...";
            document.getElementById('overlay-offline-status').style.display = 'none';
            if (pill) {
                pill.textContent = "Online";
                pill.className = "status-pill online";
            }
            
            // Start online clock tick
            state.onlineTimeInterval = setInterval(() => {
                state.earnings.onlineTime += 0.1;
                document.getElementById('stat-hours').textContent = `${state.earnings.onlineTime.toFixed(1)}h`;
            }, 360000); // Ticks every 6 mins for 0.1 hour
            
            // Start ride request loop
            triggerMockBookingLoop();
            logDebug("Captain is Online. Incoming loop started.");
        } else {
            state.status = "offline";
            document.getElementById('txt-status-header').textContent = "Status: Offline";
            document.getElementById('txt-status-desc').textContent = "Toggle Online to receive ride requests";
            document.getElementById('overlay-offline-status').style.display = 'flex';
            if (pill) {
                pill.textContent = "Offline";
                pill.className = "status-pill offline";
            }
            
            // Clean up intervals
            clearInterval(state.onlineTimeInterval);
            clearTimeout(state.requestTimeout);
            clearInterval(state.requestInterval);
            
            hideIncomingRequestOverlay();
            logDebug("Captain is Offline. Incoming loop stopped.");
        }
    }

    function triggerMockBookingLoop() {
        if (state.status !== "online" || state.activeRide || state.activeRequest) return;

        // Generate a random booking between 5 to 10 seconds
        const delay = 5000 + Math.random() * 5000;
        state.requestTimeout = setTimeout(() => {
            if (state.status !== "online" || state.activeRide || state.activeRequest) return;
            
            const req = generateMockRideRequest(state.driver.vehicleType, state.driver.lat, state.driver.lng);
            showIncomingRequest(req);
        }, delay);
    }

    /* ==========================================================================
       INCOMING BOOKING REQUEST DIALOG
       ========================================================================== */
    function showIncomingRequest(req) {
        state.activeRequest = req;
        logDebug(`Incoming request generated: ${req.id} from ${req.passengerName}`);

        // Update UI dialog contents
        document.getElementById('req-payout-amt').textContent = `₹${req.payout}`;
        document.getElementById('req-passenger-avatar').textContent = req.passengerAvatar;
        document.getElementById('req-passenger-name').textContent = req.passengerName;
        document.getElementById('req-passenger-rating').textContent = req.passengerRating;
        document.getElementById('req-pickup-name').textContent = req.pickup.name;
        document.getElementById('req-dropoff-name').textContent = req.dropoff.name;

        // Activate modal
        const overlay = document.getElementById('overlay-incoming-request');
        overlay.classList.add('active');
        if (window.lucide && typeof lucide.createIcons === 'function') {
            lucide.createIcons();
        }

        // Play alert audio beep (simulated)
        beep();

        // 15 seconds countdown
        let count = 15;
        const ringBar = document.getElementById('countdown-ring-bar');
        const timerText = document.getElementById('countdown-text');
        
        timerText.textContent = count;
        ringBar.style.strokeDashoffset = 0;

        state.requestInterval = setInterval(() => {
            count--;
            timerText.textContent = count;
            
            // Update circular ring offset (perimeter is 126px)
            const percent = count / 15;
            ringBar.style.strokeDashoffset = 126 * (1 - percent);

            if (count % 3 === 0) beep(); // Beep every 3 seconds

            if (count <= 0) {
                // Timeout -> auto decline
                clearInterval(state.requestInterval);
                declineRequest();
            }
        }, 1000);

        // Bind buttons
        const acceptBtn = document.getElementById('btn-request-accept');
        const declineBtn = document.getElementById('btn-request-decline');

        // Clear previous listeners by replacing elements or cloning
        const newAccept = acceptBtn.cloneNode(true);
        const newDecline = declineBtn.cloneNode(true);
        acceptBtn.parentNode.replaceChild(newAccept, acceptBtn);
        declineBtn.parentNode.replaceChild(newDecline, declineBtn);

        newAccept.addEventListener('click', () => {
            clearInterval(state.requestInterval);
            acceptRequest(req);
        });
        newDecline.addEventListener('click', () => {
            clearInterval(state.requestInterval);
            declineRequest();
        });
    }

    function hideIncomingRequestOverlay() {
        state.activeRequest = null;
        document.getElementById('overlay-incoming-request').classList.remove('active');
    }

    function declineRequest() {
        hideIncomingRequestOverlay();
        logDebug("Ride request declined.");
        
        // Update acceptance stats
        state.earnings.acceptanceRate = Math.max(70, Math.round(state.earnings.acceptanceRate - 4 - Math.random() * 4));
        document.getElementById('stat-acceptance').textContent = `${state.earnings.acceptanceRate}%`;

        // Queue next booking request
        triggerMockBookingLoop();
    }

    /* ==========================================================================
       ACTIVE NAVIGATION ROUTE SIMULATION STATE MACHINE
       ========================================================================== */
    function acceptRequest(req) {
        hideIncomingRequestOverlay();
        logDebug(`Ride request accepted: ${req.id}`);

        // Set navigation state
        state.activeRide = {
            request: req,
            state: 'pickup', // pickup -> otp -> dropoff -> fare
            routePoints: [],
            routeIndex: 0
        };

        // UI Widget populate
        document.getElementById('txt-nav-status').textContent = "Heading to Pickup";
        document.getElementById('nav-pulse-indicator').className = "nav-pulse-dot";
        document.getElementById('nav-client-avatar').textContent = req.passengerAvatar;
        document.getElementById('nav-client-name').textContent = req.passengerName;
        document.getElementById('nav-client-rating').textContent = req.passengerRating;
        document.getElementById('nav-pickup-address').textContent = req.pickup.address;
        document.getElementById('nav-dropoff-address').textContent = req.dropoff.address;
        document.getElementById('nav-hint-otp').textContent = req.startOtp;

        // Hide inputs/stats panel and show active nav panel
        document.getElementById('active-navigation-widget').classList.remove('hidden');
        document.getElementById('start-ride-otp-container').classList.add('hidden');

        // Draw Map markers and route to pickup
        drawActiveRideRoute(req.pickup);
    }

    function drawActiveRideRoute(destination) {
        if (!state.map) return;

        // Clean old markers & polylines
        if (state.markers.pickup) state.map.removeLayer(state.markers.pickup);
        if (state.markers.dropoff) state.map.removeLayer(state.markers.dropoff);
        if (state.routePolyline) state.map.removeLayer(state.routePolyline);

        // Add Destination Marker
        const isPickup = state.activeRide.state === 'pickup';
        const pinIcon = L.divIcon({
            html: `<div class="${isPickup ? 'pickup-marker-pin' : 'dropoff-marker-pin'}"></div>`,
            className: 'custom-div-pin',
            iconSize: [24, 24],
            iconAnchor: [12, 24]
        });

        const targetMarker = L.marker([destination.lat, destination.lng], { icon: pinIcon }).addTo(state.map);
        if (isPickup) {
            state.markers.pickup = targetMarker;
        } else {
            state.markers.dropoff = targetMarker;
        }

        // Generate Path Bezier points
        const start = { lat: state.driver.lat, lng: state.driver.lng };
        state.activeRide.routePoints = generateRoutePoints(start, destination, 60);
        state.activeRide.routeIndex = 0;

        // Draw Polyline path
        const latlngs = state.activeRide.routePoints.map(p => [p.lat, p.lng]);
        state.routePolyline = L.polyline(latlngs, { color: isPickup ? '#10B981' : '#FFD800', weight: 4 }).addTo(state.map);

        // Center map to bounds
        const bounds = L.latLngBounds([start, destination]);
        state.map.fitBounds(bounds.pad(0.15));

        // Start movement simulation tick
        simulateNavigationTicks();
    }

    function simulateNavigationTicks() {
        clearInterval(state.simulationInterval);
        
        state.simulationInterval = setInterval(() => {
            const ride = state.activeRide;
            if (!ride) return;

            if (ride.routeIndex < ride.routePoints.length) {
                // Update driver coordinate along path
                const pos = ride.routePoints[ride.routeIndex];
                state.driver.lat = pos.lat;
                state.driver.lng = pos.lng;
                
                updateDriverMarkerOnMap();
                
                // Keep marker in bounds or center map
                if (ride.routeIndex % 10 === 0) {
                    state.map.panTo([pos.lat, pos.lng]);
                }

                ride.routeIndex++;
            } else {
                // Vehicle arrived at waypoint
                clearInterval(state.simulationInterval);
                handleWaypointArrival();
            }
        }, 150);
    }

    function handleWaypointArrival() {
        const ride = state.activeRide;
        if (!ride) return;

        if (ride.state === 'pickup') {
            logDebug("Arrived at pickup location. Waiting for start OTP.");
            beep();
            
            // Switch navigation action panel to ask OTP
            ride.state = 'otp';
            document.getElementById('txt-nav-status').textContent = "Arrived at Pickup";
            document.getElementById('nav-pulse-indicator').className = "nav-pulse-dot green";
            
            const actionBtn = document.getElementById('btn-navigation-action');
            actionBtn.textContent = "Start Ride (Verify OTP)";
            document.getElementById('start-ride-otp-container').classList.remove('hidden');

            // Listen for start ride verification
            const verifyCodeBtn = actionBtn; // Action button serves as validator
            
            // Clear old listener
            const newBtn = verifyCodeBtn.cloneNode(true);
            verifyCodeBtn.parentNode.replaceChild(newBtn, verifyCodeBtn);

            newBtn.addEventListener('click', () => {
                const enteredOtp = document.getElementById('input-start-ride-otp').value.trim();
                const startError = document.getElementById('start-otp-error');
                
                if (enteredOtp === ride.request.startOtp) {
                    // Correct OTP -> start trip navigating to dropoff
                    startError.classList.add('hidden');
                    document.getElementById('input-start-ride-otp').value = '';
                    
                    ride.state = 'dropoff';
                    document.getElementById('txt-nav-status').textContent = "Ride in Progress";
                    document.getElementById('start-ride-otp-container').classList.add('hidden');
                    newBtn.textContent = "Navigate to Destination";
                    newBtn.disabled = true; // disabled during navigation simulation

                    // Draw route to dropoff destination
                    drawActiveRideRoute(ride.request.dropoff);
                } else {
                    // Incorrect OTP
                    startError.classList.remove('hidden');
                    document.getElementById('input-start-ride-otp').value = '';
                    document.getElementById('input-start-ride-otp').focus();
                }
            });

        } else if (ride.state === 'dropoff') {
            logDebug("Arrived at destination dropoff.");
            beep();
            
            ride.state = 'fare';
            document.getElementById('txt-nav-status').textContent = "Ride Completed";
            
            const actionBtn = document.getElementById('btn-navigation-action');
            actionBtn.disabled = false;
            actionBtn.textContent = "Collect Fare & End Ride";

            // Clear old click listener
            const newBtn = actionBtn.cloneNode(true);
            actionBtn.parentNode.replaceChild(newBtn, actionBtn);

            newBtn.addEventListener('click', () => {
                showFareCollection();
            });
        }
    }

    /* ==========================================================================
       FARE COLLECTION SCREEN & STATISTICS UPDATES
       ========================================================================== */
    function showFareCollection() {
        const ride = state.activeRide;
        if (!ride) return;

        // Hide navigation details, open fare collection panel
        document.getElementById('active-navigation-widget').classList.add('hidden');
        
        document.getElementById('fare-collected-amount').textContent = `₹${ride.request.payout + 10}`; // Payout + ₹10 Tip
        document.getElementById('fare-breakdown-dist').textContent = `${ride.request.rideDistance} km`;
        document.getElementById('fare-breakdown-payout').textContent = `₹${ride.request.payout}`;
        document.getElementById('fare-breakdown-total').textContent = `₹${ride.request.payout + 10}`;

        document.getElementById('fare-collection-widget').classList.remove('hidden');

        // Confirm button
        const confirmBtn = document.getElementById('btn-complete-fare-done');
        const newBtn = confirmBtn.cloneNode(true);
        confirmBtn.parentNode.replaceChild(newBtn, confirmBtn);

        newBtn.addEventListener('click', () => {
            // Update stats
            const totalCollected = ride.request.payout + 10;
            state.earnings.today += totalCollected;
            state.earnings.rides += 1;
            
            // Sync with weekly chart Fri value
            state.weeklyEarnings.fri += totalCollected;

            // Add completed ride to local History array
            const timeString = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            state.history.unshift({
                date: `Today, ${timeString}`,
                payout: totalCollected,
                pickupName: ride.request.pickup.name,
                dropoffName: ride.request.dropoff.name
            });

            // Update UI elements
            updateEarningsDisplay();
            updateHistoryUI();

            // Clear active markers & line
            if (state.markers.pickup) state.map.removeLayer(state.markers.pickup);
            if (state.markers.dropoff) state.map.removeLayer(state.markers.dropoff);
            if (state.routePolyline) state.map.removeLayer(state.routePolyline);
            state.markers.pickup = null;
            state.markers.dropoff = null;
            state.routePolyline = null;

            // Reset Active Ride state
            state.activeRide = null;
            updateDriverMarkerOnMap();

            // Hide fare widget
            document.getElementById('fare-collection-widget').classList.add('hidden');
            logDebug("Fare collection completed. Back to Online state.");

            // Queue next mock ride request
            triggerMockBookingLoop();
        });
    }

    function updateEarningsDisplay() {
        document.getElementById('stat-earnings').textContent = `₹${state.earnings.today}`;
        document.getElementById('stat-rides').textContent = state.earnings.rides;
        
        // Update payouts panel
        const totalFri = state.weeklyEarnings.fri;
        document.getElementById('wallet-withdrawable').textContent = `₹${1240 + totalFri}`;

        // Update bar chart height values (mon-sun)
        const weeklyValues = state.weeklyEarnings;
        const maxVal = Math.max(...Object.values(weeklyValues), 1000); // Normalize chart to highest payout

        const setBarHeight = (id, val) => {
            const el = document.getElementById(id);
            if (el) {
                const percent = Math.max(5, Math.round((val / maxVal) * 100));
                el.style.height = `${percent}%`;
            }
        };

        setBarHeight('bar-mon', weeklyValues.mon);
        setBarHeight('bar-tue', weeklyValues.tue);
        setBarHeight('bar-wed', weeklyValues.wed);
        setBarHeight('bar-thu', weeklyValues.thu);
        setBarHeight('bar-fri', weeklyValues.fri);
        setBarHeight('bar-sat', weeklyValues.sat);
        setBarHeight('bar-sun', weeklyValues.sun);
    }

    function updateHistoryUI() {
        const container = document.getElementById('trips-history-container');
        container.innerHTML = '';

        if (state.history.length === 0) {
            container.innerHTML = `<p style="font-size:13px;color:var(--text-secondary);text-align:center;padding:20px;">You haven't completed any rides yet today.</p>`;
            return;
        }

        state.history.forEach(trip => {
            const card = document.createElement('div');
            card.className = 'history-card';
            card.innerHTML = `
                <div class="history-card-header">
                    <span>${trip.date}</span>
                    <span class="payout-green">+₹${trip.payout}</span>
                </div>
                <div class="history-locs">
                    <div class="history-loc-item">
                        <i data-lucide="circle-dot" class="green" style="width:12px;height:12px;"></i>
                        <span>${trip.pickupName}</span>
                    </div>
                    <div class="history-loc-item">
                        <i data-lucide="map-pin" class="red" style="width:12px;height:12px;"></i>
                        <span>${trip.dropoffName}</span>
                    </div>
                </div>
            `;
            container.appendChild(card);
        });
        if (window.lucide && typeof lucide.createIcons === 'function') {
            lucide.createIcons();
        }
    }

    /* ==========================================================================
       HELPERS & AUDIO BEEP SIMULATION
       ========================================================================== */
    function beep() {
        try {
            const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioCtx.createOscillator();
            const gainNode = audioCtx.createGain();

            oscillator.connect(gainNode);
            gainNode.connect(audioCtx.destination);

            oscillator.type = 'sine';
            oscillator.frequency.value = 880; // A5 pitch
            gainNode.gain.setValueAtTime(0.05, audioCtx.currentTime); // Low volume
            oscillator.start();
            
            setTimeout(() => {
                oscillator.stop();
                audioCtx.close();
            }, 150); // 150ms length
        } catch (e) {
            // AudioContext block browser safety fallback
            logDebug("Audio Beep blocked by browser auto-play policy.");
        }
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeApp);
} else {
    initializeApp();
}
