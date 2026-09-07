document.addEventListener(
    'DOMContentLoaded',
    () => {

        const loginButton =
            document.getElementById(
                'facebook-login-button'
            );

        const logoutButton =
            document.getElementById(
                'logout-button'
            );


        // ---------------------------------------------------------
        // Login button loading state
        // ---------------------------------------------------------

        if (loginButton) {

            loginButton.addEventListener(
                'click',
                () => {

                    loginButton.classList.add(
                        'is-loading'
                    );

                    const label =
                        loginButton.querySelector(
                            '.button-label'
                        );

                    if (label) {

                        label.textContent =
                            'Connecting to Facebook';
                    }
                }
            );
        }


        // ---------------------------------------------------------
        // Logout confirmation
        // ---------------------------------------------------------

        if (logoutButton) {

            logoutButton.addEventListener(
                'click',
                (event) => {

                    const confirmed =
                        window.confirm(
                            'Are you sure you want to log out?'
                        );

                    if (!confirmed) {

                        event.preventDefault();
                    }
                }
            );
        }
    }
);