const catalogGrid = document.getElementById('catalog-grid'); 
const playerWrapper = document.getElementById('player-wrapper'); 
const episodeList = document.getElementById('episode-list'); 
const videoPlayer = document.getElementById('video-player'); 
const playingTitle = document.getElementById('playing-title'); 
let hlsInstance = null; 

async function searchAnime() { 
    const query = document.getElementById('search-input').value; 
    if (!query) return; 
    
    catalogGrid.innerHTML = '<p>Searching across repositories...</p>'; 
    
    try { 
        const res = await fetch(`/api/search?q=${encodeURIComponent(query)}`); 
        const data = await res.json(); 
        
        catalogGrid.innerHTML = ''; 
        if(data.length === 0) {
            catalogGrid.innerHTML = '<p>No results found.</p>';
            return;
        }

        data.forEach(anime => { 
            const card = document.createElement('div'); 
            card.className = 'card'; 
            card.innerHTML = `
                <img src="${anime.image}" alt="${anime.title}">
                <h3>${anime.title}</h3>
            `; 
            card.onclick = () => loadAnimeStreams(anime.title); 
            catalogGrid.appendChild(card); 
        }); 
    } catch (err) { 
        catalogGrid.innerHTML = '<p>Error looking up catalog listings.</p>'; 
    } 
} 

async function loadAnimeStreams(title) { 
    playerWrapper.style.display = 'block'; 
    playingTitle.innerText = `Loading links for: ${title}...`; 
    episodeList.innerHTML = ''; 
    window.scrollTo({ top: 0, behavior: 'smooth' });

    try { 
        const res = await fetch(`/api/anime/${encodeURIComponent(title)}/episodes`); 
        const data = await res.json(); 
        
        playingTitle.innerText = `Watching: ${title}`; 
        
        data.episodes.forEach(ep => { 
            const btn = document.createElement('button'); 
            btn.className = 'ep-btn'; 
            btn.innerText = `Ep ${ep.number}`; 
            btn.onclick = () => injectVideoSource(ep.id); 
            episodeList.appendChild(btn); 
        }); 
    } catch (err) { 
        playingTitle.innerText = 'Failed loading source pipelines.'; 
    } 
} 

async function injectVideoSource(episodeId) { 
    try { 
        const res = await fetch(`/api/stream/${episodeId}`); 
        const data = await res.json(); 
        
        // Target index position safely inside arrays returned from Consumet
        const streamUrl = data.sources[0].url; 

        if (Hls.isSupported()) { 
            if (hlsInstance) hlsInstance.destroy(); 
            
            hlsInstance = new Hls(); 
            hlsInstance.loadSource(streamUrl); 
            hlsInstance.attachMedia(videoPlayer); 
            hlsInstance.on(Hls.Events.MANIFEST_PARSED, () => { 
                videoPlayer.play(); 
            }); 
        } else if (videoPlayer.canPlayType('application/vnd.apple.mpegurl')) { 
            videoPlayer.src = streamUrl; 
            videoPlayer.addEventListener('loadedmetadata', () => { 
                videoPlayer.play(); 
            }); 
        } 
    } catch (err) { 
        alert('Failed configuring live source stream payload.'); 
    } 
}
