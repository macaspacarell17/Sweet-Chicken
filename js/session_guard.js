/*
 * Session guard.
 * If the server reports that the session is no longer valid (HTTP 401) — for
 * example because the Administrator deactivated this account — send the user
 * back to the login page instead of leaving a dead dashboard on screen.
 */
(function () {
    const originalFetch = window.fetch;
    let redirecting = false;
    window.fetch = async function () {
        const response = await originalFetch.apply(this, arguments);
        if (response.status === 401 && !redirecting) {
            redirecting = true;
            window.location.href = 'loginpage.php?view=login';
        }
        return response;
    };
})();
